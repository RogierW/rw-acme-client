<?php

use Rogierw\RwAcme\Api as AcmeApi;

// The + and & characters are being replaced with _ in the account name. This test
// asserts that by adding a (part of) a hash prevents collisions.
test('setting an account name without collision', function () {
    $api = new AcmeApi('test+test@example.com', '/tmp/acme-test');
    $name1 = $api->keyStorage->getAccountName();

    $api->useAccount('test&test@example.com');
    $name2 = $api->keyStorage->getAccountName();

    expect($name1)->not()->toBe($name2);
});
