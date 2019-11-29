# Let’s Encrypt ACME client written in PHP

This library allows you to request, renew and revoke SSL certificates provided by Let's Encrypt.

## Requirements
- PHP ^7.1
- cURL extension

## Installation
You can install the package via composer:

`composer require rogierw/letsencrypt-client`

## Usage

You can create an instance of `Rogierw\Letsencrypt\Api` client.

```php
$client = new Api('test@example.com', __DIR__ . '/__account');
```

### Creating an account
```php
if (!$client->account()->exists()) {
    $account = $client->account()->create();
}

// Or get an existing account.
$account = $client->account()->get();
```

### Creating an order
```php
$order = $client->order()->new($account, ['letsencrypt.example.com']);
```

### Getting the order
```php
$order = $client->order()->get($order->id);
```

### Getting the DCV status
```php
$domainValidationStatus = $client->domainValidation()->status($order);

// Get the first element in the array. Usually there is only one element.
$domainValidation = $domainValidationStatus[0];
```

### Start HTTP challenge
```php
if ($domainValidation->isPending()) {
    // Get the data for the HTTP challenge; filename and content.
    $validationData = $client->domainValidation()->getFileValidationData($domainValidation);

    $client->domainValidation()->start($account, $domainValidation);

    $domainValidationStatus = $client->domainValidation()->status($order);
    $domainValidation = $domainValidationStatus[0];
}
```

### Finalizing order
```php
if ($order->isReady() && $domainValidation->isValid() && $order->isNotFinalized()) {
    $client->order()->finalize($order, $csr);
}
```

### Getting the actual certificate
```php
if ($order->isFinalized()) {
    $certificateBundle = $client->certificate()->getBundle($order);
}
```

### Revoke a certificate
```php
if ($order->isValid()) {
    $client->certificate()->revoke($certificateBundle->fullchain);
}
```
