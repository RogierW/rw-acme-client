<?php

namespace Rogierw\RwAcme\Interfaces;

interface KeyStorageInterface
{
    public function setAccountName(string $accountName): self;
    public function getPrivateKey(): string;
    public function getPublicKey(): string;
    public function exists(): bool;
    public function generateNewKeys(): bool;
}
