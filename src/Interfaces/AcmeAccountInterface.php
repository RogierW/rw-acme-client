<?php

namespace Rogierw\RwAcme\Interfaces;

interface AcmeAccountInterface
{
    public function setEmailAddress(string $emailAddress): self;

    public function getEmailAddress(): string;

    public function getPrivateKey(): string;

    public function getPublicKey(): string;

    public function exists(): bool;

    public function generateNewKeys(string $keyType = 'RSA'): bool;
}
