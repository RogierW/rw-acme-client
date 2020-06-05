# Let’s Encrypt ACME client written in PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rogierw/rw-acme-client.svg?style=flat-square)](https://packagist.org/packages/rogierw/rw-acme-client)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/RogierW/rw-acme-client.svg?style=flat-square)](https://scrutinizer-ci.com/g/RogierW/rw-acme-client/?branch=master)
[![StyleCI](https://github.styleci.io/repos/224902862/shield?style=flat-square&branch=master)](https://github.styleci.io/repos/224902862)

This library allows you to request, renew and revoke SSL certificates provided by Let's Encrypt.

## Requirements
- PHP ^7.1
- OpenSSL >= 1.0.1
- cURL extension

## Installation
You can install the package via composer:

`composer require rogierw/rw-acme-client`

## Usage

You can create an instance of `Rogierw\RwAcme\Api` client.

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

#### Creating an order
```php
$order = $client->order()->new($account, ['example.com']);
```

#### Getting an order
```php
$order = $client->order()->get($order->id);
```

### Domain validation

#### Getting the DCV status
```php
$validationStatus = $client->domainValidation()->status($order);
```

#### http-01

Get the name and content for the validation file:
```php
// Get the data for the HTTP challenge; filename and content.
$validationData = $client->domainValidation()->getFileValidationData($validationStatus);
```

This returns an array:
```php
Array
(
    [0] => Array
        (
            [type] => http-01
            [identifier] => example.com
            [filename] => sqQnDYNNywpkwuHeU4b4FTPI2mwSrDF13ti08YFMm9M
            [content] => sqQnDYNNywpkwuHeU4b4FTPI2mwSrDF13ti08YFMm9M.kB7_eWSDdG3aWIaPSp6Uy4vLBbBI5M0COvM-AZOBcoQ
        )
)
```

The Let's Encrypt validation server will make a request to the following URL:
```
http://example.com/.well-known/acme-challenge/sqQnDYNNywpkwuHeU4b4FTPI2mwSrDF13ti08YFMm9M
```

#### dns-01
@TODO

#### Start domain validation
```php
try {
    $client->domainValidation()->start($account, $validationStatus[0]);
} catch (DomainValidationException $exception) {
    // The local HTTP challenge test has been failed...
}
```

#### Generating a CSR
```php
$privateKey = \Rogierw\RwAcme\Support\OpenSsl::generatePrivateKey();
$csr = \Rogierw\RwAcme\Support\OpenSsl::generateCsr(['example.com'], $privateKey);
```

#### Finalizing order
```php
if ($order->isReady() && $client->domainValidation()->challengeSucceeded($order, DomainValidation::TYPE_HTTP)) {
    $client->order()->finalize($order, $csr);
}
```

#### Getting the actual certificate
```php
if ($order->isFinalized()) {
    $certificateBundle = $client->certificate()->getBundle($order);
}
```

#### Revoke a certificate
```php
if ($order->isValid()) {
    $client->certificate()->revoke($certificateBundle->fullchain);
}
```
