<?php

namespace Revlenuwe\Gearer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;

class Gearer
{
    const API_URL = 'https://gateway.gear.mycelium.com';


    public string $gatewayId;
    public string $gatewaySecret;
    public Client $client;

    public function __construct()
    {
        $this->gatewayId = null;
        $this->gatewaySecret = null;
        $this->client = new Client(['base_uri' => self::API_URL]);
    }


    public function setConfig($gatewayId, $gatewaySecret): self
    {
        $this->gatewayId = $gatewayId;
        $this->gatewaySecret = $gatewaySecret;

        return $this;
    }

    public function createOrder($amount, $keychainId)
    {
        $url = $this->apiEndpoint('orders');

        return $this->makeRequest('POST', $url, [
            'amount' => $amount,
            'keychain_id' => $keychainId
        ]);
    }

    public function cancelOrder($orderOrPaymentId)
    {
        $url = $this->apiEndpoint("orders/{$orderOrPaymentId}/cancel");

        return $this->makeRequest('POST',$url);
    }

    public function checkOrderStatusManually($paymentId)
    {
        $url = $this->apiEndpoint("orders/{$paymentId}");

        return $this->makeRequest('GET', $url);
    }

    public function handleOrderStatusCallback(Request $request)
    {
        $requestSignature = $request->headers->get('X-Signature');

        $nonceHash = $this->generateNonceHash(false);
        $signatureHash = $this->generateSignatureHash($request->getMethod() . $request->getRequestUri() . $nonceHash);

        if($requestSignature === $signatureHash){
            return $request;
        }

        return false;
    }


    public function getLastKeychainId() : int
    {
        $url = $this->apiEndpoint('last_keychain_id');

        $response = $this->makeRequest('GET',$url);

        return $response->last_keychain_id;
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

}
