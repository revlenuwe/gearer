<?php

namespace Revlenuwe\Gearer;

use GuzzleHttp\Client;

class Gearer
{
    const API_URL = 'https://gateway.gear.mycelium.com';

    public string $gatewayId;
    public string $gatewaySecret;

    public function __construct()
    {
        $this->gatewayId = null;
        $this->gatewaySecret = null;
    }


    public function setConfig($gatewayId, $gatewaySecret): self
    {
        $this->gatewayId = $gatewayId;
        $this->gatewaySecret = $gatewaySecret;

        return $this;
    }

    public function createOrder($amount, $keychainId)
    {
        $url = $this->endpoint('orders');

        return $this->makeRequest('POST', $url, [
            'amount' => $amount,
            'keychain_id' => $keychainId
        ]);
    }

    public function cancelOrder($orderOrPaymentId)
    {
        $url = $this->endpoint("orders/{$orderOrPaymentId}/cancel");

        return $this->makeRequest('POST',$url);
    }


    public function getLastKeychainId()
    {
        $url = $this->endpoint('last_keychain_id');

        $response = $this->makeRequest('GET',$url);

        return $response->last_keychain_id;
    }


    private function endpoint($endpoint){
        return "/gateways/{$this->gatewayId}/".$endpoint;
    }

    private function makeRequest($method, $uri, $params = [])
    {
        $client = new Client(['base_uri' => self::API_URL]);

        $hashes = $this->prepareHeadersHashes($method, $uri ,$params);

        $request = $client->request($method, $uri,[
            'form_params' => $params,
            'headers' => [
                'X-Nonce' => $hashes['nonce'],
                'X-Signature' => $hashes['signature']
            ]
        ]);

        return json_decode($request->getBody()->getContents());
    }

    private function prepareHeadersHashes($method, $url, $params)
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
