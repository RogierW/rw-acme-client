# Let’s Encrypt ACME client written in PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rogierw/rw-acme-client.svg?style=flat-square)](https://packagist.org/packages/rogierw/rw-acme-client)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/RogierW/rw-acme-client.svg?style=flat-square)](https://scrutinizer-ci.com/g/RogierW/rw-acme-client/?branch=master)
[![StyleCI](https://github.styleci.io/repos/224902862/shield?style=flat-square&branch=master)](https://github.styleci.io/repos/224902862)

This library allows you to request, renew and revoke SSL certificates provided by Let's Encrypt.

If you're looking for an easy-to-use CLI tool for managing your LE certificates, take a look at the [RW ACME CLI](https://github.com/RogierW/rw-acme-cli) project.

## Requirements
- PHP ^8.1
- OpenSSL >= 1.0.1
- cURL extension
- JSON extension

* **Note**: v2 of this package only supports `php:^8.1`. If you're looking for the older version, check out [v1](https://github.com/RogierW/rw-acme-client/tree/v1).

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

### Creating an order
```php
$order = $client->order()->new($account, ['example.com']);
```

#### Renewal
Simply create a new order to renew an existing certificate as described above. Ensure that you use the same account as you did for the initial request.

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
$validationData = $client->domainValidation()->getValidationData($validationStatus, AuthorizationChallengeEnum::HTTP);
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

Get the name and the value for the TXT record:
```php
// Get the data for the DNS challenge.
$validationData = $client->domainValidation()->getValidationData($validationStatus, AuthorizationChallengeEnum::DNS);
```

This returns an array:
```php
Array
(
    [0] => Array
        (
            [type] => dns-01
            [identifier] => example.com
            [name] => _acme-challenge
            [value] => 8hSNdxGNkx4MI7ZN5F8uZj3cTSMX92SGMCMHQMh0cMA
        )
)
```

#### Start domain validation

##### http-01
```php
try {
    $client->domainValidation()->start($account, $validationStatus[0], AuthorizationChallengeEnum::HTTP);
} catch (DomainValidationException $exception) {
    // The local HTTP challenge test has been failed...
}
```

##### dns-01
```php
try {
    $client->domainValidation()->start($account, $validationStatus[0], AuthorizationChallengeEnum::DNS);
} catch (DomainValidationException $exception) {
    // The local DNS challenge test has been failed...
}
```

#### Generating a CSR
```php
$privateKey = \Rogierw\RwAcme\Support\OpenSsl::generatePrivateKey();
$csr = \Rogierw\RwAcme\Support\OpenSsl::generateCsr(['example.com'], $privateKey);
```

#### Finalizing order
```php
if ($order->isReady() && $client->domainValidation()->allChallengesPassed($order)) {
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
