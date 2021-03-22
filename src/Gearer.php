<?php

namespace Revlenuwe\Gearer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;

class Gearer
{
    private $baseUrl;
    private $gatewayId;
    private $gatewaySecret;
    private $client;
    private $endpoint;

    public function __construct()
    {
        $this->baseUrl = config('gearer.gateway_url');
        $this->gatewayId = config('gearer.gateway_id');
        $this->gatewaySecret = config('gearer.gateway_secret');
        $this->client = new Client(['base_uri' => $this->baseUrl]);
    }

    public function setConfig(string $gatewayId, string $gatewaySecret): Gearer
    {
        $this->gatewayId = $gatewayId;
        $this->gatewaySecret = $gatewaySecret;

        return $this;
    }

    public function createOrder(float $amount, int $keychainId, array $callbackData = [])
    {
        $callbackData = array_merge(config('gearer.defaults.callback_data'), $callbackData);

        return $this
            ->apiEndpoint('orders')
            ->makeRequest('POST', [
                'amount' => $this->formatAmount($amount),
                'keychain_id' => $keychainId,
                'callback_data' => json_encode($callbackData)
            ]);
    }

    public function cancelOrder($orderOrPaymentId): bool
    {
        $response = $this
            ->apiEndpoint("orders/$orderOrPaymentId/cancel")
            ->makeRequest('POST');

        return is_null($response);
    }

    public function checkOrderStatusManually($paymentId)
    {
        return $this
            ->apiEndpoint("orders/$paymentId")
            ->makeRequest('GET');
    }

    public function handleOrderStatusCallback(?Request $request = null): array
    {
        $request = $request ?: request();

        $requestSignature = $request->headers->get('X-Signature');

        $nonceHash = $this->generateNonceHash(false);
        $signatureHash = $this->generateSignatureHash($request->getMethod() . $request->getRequestUri() . $nonceHash);

        if ($requestSignature === $signatureHash) {
            return $request->toArray();
        }

        return [];
    }

    public function getLastKeychainId(): int
    {
        $response = $this
            ->apiEndpoint('last_keychain_id')
            ->makeRequest('GET');

        return $response->last_keychain_id;
    }

    public function getOrderWebsocketUrl(string $orderId): string
    {
        return $this
            ->apiEndpoint("orders/{$orderId}/websocket")
            ->fullUrl();
    }

    private function apiEndpoint($endpoint): Gearer
    {
        $this->endpoint = "/gateways/{$this->gatewayId}/$endpoint";
        return $this;
    }

    private function fullUrl(): string
    {
        return $this->baseUrl . $this->endpoint;
    }

    /**
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function makeRequest(string $method, array $params = [])
    {
        $hashes = $this->prepareHeadersHashes($method, $params);

        try {
            $request = $this->client->request($method, $this->endpoint, [
                'form_params' => $params,
                'headers' => [
                    'X-Nonce' => $hashes['nonce'],
                    'X-Signature' => $hashes['signature']
                ]
            ]);
        } catch (ClientException $e) {
            return json_decode($e->getResponse()->getBody());
        }

        return json_decode($request->getBody());
    }

    /**
     * @param string $method
     * @param mixed $params May be an array or object containing properties.
     * @return array
     */
    private function prepareHeadersHashes(string $method, $params): array
    {
        $nonceHash = $this->generateNonceHash();
        $queryParams = http_build_query($params);
        $signatureHash = $this->generateSignatureHash($method . $this->endpoint . $queryParams . $nonceHash);

        return [
            'nonce' => $nonceHash,
            'signature' => $signatureHash
        ];
    }

    private function generateNonceHash($withUnique = true): string
    {
        $uniqueValue = $withUnique ? round(time() * 1000) : null;

        return hash('sha512', $uniqueValue, true);
    }

    public function generateSignatureHash(string $signatureString): string
    {
        $signature = hash_hmac(
            'sha512', $signatureString, $this->gatewaySecret, true
        );

        return base64_encode($signature);
    }

    private function formatAmount(float $amount): float
    {
        return number_format($amount, 2, '.', '');
    }

}
