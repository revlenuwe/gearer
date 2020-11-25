<?php

namespace Revlenuwe\Gearer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

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
        $unique = round(time() * 1000);
        $body = '';

        $query = http_build_query($params);

        $bodyHash = hash('sha512',$unique.$body, true);

        $signature = hash_hmac(
            'sha512',
            $method . $url . $query .$bodyHash,
            $this->gatewaySecret,
            true
        );
        $encodedSignature = base64_encode($signature);

        return [
            'nonce' => $bodyHash,
            'signature' => $encodedSignature
        ];
    }

}
