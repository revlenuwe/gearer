<?php

namespace Revlenuwe\Gearer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;

class Gearer
{
    const API_URL = 'https://gateway.gear.mycelium.com';


    public $gatewayId;
    public $gatewaySecret;
    public $client;

    public function __construct()
    {
        $this->gatewayId = config('gearer.gateway_id');
        $this->gatewaySecret = config('gearer.gateway_secret');
        $this->client = new Client(['base_uri' => self::API_URL]);
    }


    public function setConfig($gatewayId, $gatewaySecret): self
    {
        $this->gatewayId = $gatewayId;
        $this->gatewaySecret = $gatewaySecret;

        return $this;
    }

    public function createOrder(float $amount, int $keychainId, array $callbackData = [])
    {
        $url = $this->apiEndpoint('orders');

        $callbackData = array_merge(config('gearer.defaults.callback_data'), $callbackData);

        return $this->makeRequest('POST', $url, [
            'amount' => $this->formatAmount($amount),
            'keychain_id' => $keychainId,
            'callback_data' => json_encode($callbackData)
        ]);
    }

    public function cancelOrder($orderOrPaymentId) : bool
    {
        $url = $this->apiEndpoint("orders/{$orderOrPaymentId}/cancel");

        $response = $this->makeRequest('POST',$url);

        return $response == null;
    }

    public function checkOrderStatusManually($paymentId)
    {
        $url = $this->apiEndpoint("orders/{$paymentId}");

        return $this->makeRequest('GET', $url);
    }

    public function handleOrderStatusCallback(?Request $request = null)
    {
        $request = $request ?: request();

        $requestSignature = $request->headers->get('X-Signature');

        $nonceHash = $this->generateNonceHash(false);
        $signatureHash = $this->generateSignatureHash($request->getMethod() . $request->getRequestUri() . $nonceHash);

        if($requestSignature === $signatureHash){
            return $request->toArray();
        }

        return false;
    }

    public function getLastKeychainId() : int
    {
        $url = $this->apiEndpoint('last_keychain_id');

        $response = $this->makeRequest('GET',$url);

        return $response->last_keychain_id;
    }

    public function getOrderWebsocketUrl(string $orderId) : string
    {
        return self::API_URL.$this->apiEndpoint("orders/{$orderId}/websocket");
    }

    private function apiEndpoint($endpoint) : string
    {
        return "/gateways/{$this->gatewayId}/".$endpoint;
    }

    private function makeRequest($method, $url, $params = [])
    {
        $hashes = $this->prepareHeadersHashes($method, $url ,$params);

        try {
            $request = $this->client->request($method, $url,[
                'form_params' => $params,
                'headers' => [
                    'X-Nonce' => $hashes['nonce'],
                    'X-Signature' => $hashes['signature']
                ]
            ]);
        }catch (ClientException $e) {
            return json_decode($e->getResponse()->getBody());
        }

        return json_decode($request->getBody());
    }

    private function prepareHeadersHashes($method, $url, $params) : array
    {
        $nonceHash = $this->generateNonceHash();

        $queryParams = http_build_query($params);

        $signatureHash = $this->generateSignatureHash($method . $url . $queryParams . $nonceHash);

        return [
            'nonce' => $nonceHash,
            'signature' => $signatureHash
        ];
    }

    private function generateNonceHash($withUnique = true) : string
    {
        $uniqueValue = $withUnique ? round(time() * 1000) : null;

        return hash('sha512', $uniqueValue , true);
    }

    public function generateSignatureHash(string $signatureString) : string
    {
        $signature = hash_hmac(
            'sha512', $signatureString, $this->gatewaySecret, true
        );

        return base64_encode($signature);
    }

    private function formatAmount(float $amount) : float
    {
        return number_format($amount, 2 ,'.', '');
    }

}
