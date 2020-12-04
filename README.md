# MyceliumGear gateway Laravel
![GEAR](https://image.prntscr.com/image/2VuH8BDBTBS3wCtCi7FIdg.png)

Implementation MyceliumGear gateway payment processing support for Laravel

For more information check [Mycelium Gear](https://gear.mycelium.com/)

## Installation

Via Composer

``` bash
$ composer require revlenuwe/gearer
```

Register the service provider and facade if you work with Laravel 5.4:

``` php
// config/app.php

'providers' => [
    ...
    Revlenuwe\Gearer\GearerServiceProvider::class,
];


'aliases' => [
    ...
    Revlenuwe\Gearer\Facades\Gearer::class,
];
```
For Laravel 5.5+ they will be registered automatically


You can publish the `gearer.php` config with:

``` bash
$ php artisan vendor:publish --provider="Revlenuwe\Gearer\GearerServiceProvider"
```

## Usage


##### Creating order:

``` php
$order = Gearer::createOrder($amount, $lastKeyChainId);
```

##### Canceling order:

``` php
$result = Gearer::cancelOrder($orderOrPaymentId);
```

##### Receiving Last Keychain ID:

``` php
$lastKeyChainId = Gearer::getLastKeychainId();

// 1
```

##### Checking Order Status Manually:

``` php
$orderData = Gearer::checkOrderStatusManually($paymentId);

/*
{
  "status": 2,
  "amount": 7894000,
  "address": "1NZov2nm6gRCGW6r4q1qHtxXurrWNpPr1q",
  "transaction_ids": ["f0f9205e41bf1b79cb7634912e86bb840cedf8b1d108bd2faae1651ca79a5838"],
  "id": 1,
  "payment_id": "y78033435ea02f024f9abdfd04adabe314a322a0d353c33beb3acb7d97f1bdeb",
  "amount_in_btc": "0.07894",
  "amount_paid_in_btc": "0.07894",
  "keychain_id": 3,
  "last_keychain_id": 3
}
*/
```

##### Order Websocket URL:

``` php
$websocketUrl = Gearer::getOrderWebsocketUrl($orderId);


// https://gateway.gear.mycelium.com/gateways/:api_gateway_id/orders/:orderId:/websocket

```

##### Receiving Order Status Change Callback:

``` php
public function handleCallback(Request $request)
{
    //Passing $request is optional
    $order = Gearer::handleOrderStatusCallback($request);

    if($order !== false){
        /*
        [
          "status": 2,
          "amount": 7894000,
          "address": "1NZov2nm6gRCGW6r4q1qHtxXurrWNpPr1q",
          "transaction_ids": ["f0f9205e41bf1b79cb7634912e86bb840cedf8b1d108bd2faae1651ca79a5838"],
          "id": 1,
          "payment_id": "y78033435ea02f024f9abdfd04adabe314a322a0d353c33beb3acb7d97f1bdeb",
          "amount_in_btc": "0.07894",
          "amount_paid_in_btc": "0.07894",
          "keychain_id": 3,
          "last_keychain_id": 3
        ]
        */
    }
}
```

##### Setting config on the fly:

``` php
$gearer = Gearer::setConfig($gatewayId, $gatewaySecret);

$lastKeychainId = $gearer->getLastKeychainId();
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
