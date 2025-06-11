<?php

namespace Rogierw\RwAcme\Support;

use Rogierw\RwAcme\Exceptions\LetsEncryptClientException;
use Rogierw\RwAcme\Interfaces\AcmeAccountInterface;

class LocalFileAccount implements AcmeAccountInterface
{
    private string $accountName;

    public function __construct(private string $accountKeysPath)
    {
        // Make sure the path ends with a slash.
        $this->accountKeysPath = rtrim($this->accountKeysPath, '/').'/';
        $this->accountName = 'account_'.substr(hash('sha256', $this->accountKeysPath), 0, 16);
    }

    public function getPrivateKey(): string
    {
        return $this->getKey('private');
    }

    public function getPublicKey(): string
    {
        return $this->getKey('public');
    }

    public function exists(): bool
    {
        if (is_dir($this->accountKeysPath)) {
            return is_file($this->accountKeysPath.$this->getKeyName('private'))
                && is_file($this->accountKeysPath.$this->getKeyName('public'));
        }

        return false;
    }

    public function generateNewKeys(string $keyType = 'RSA'): bool
    {
        if ($keyType !== 'RSA') {
            throw new LetsEncryptClientException('Key type is not supported.');
        }

        $concurrentDirectory = rtrim($this->accountKeysPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (!is_dir($concurrentDirectory) && !mkdir($concurrentDirectory) && !is_dir($concurrentDirectory)) {
            throw new LetsEncryptClientException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        $keys = CryptRSA::generate();

        if (!isset($keys['privateKey'], $keys['publicKey'])) {
            throw new LetsEncryptClientException('Key generation failed.');
        }

        $privateKeyPath = $concurrentDirectory.$this->getKeyName('private');
        $publicKeyPath = $concurrentDirectory.$this->getKeyName('public');

        if (file_put_contents($privateKeyPath, $keys['privateKey']) === false ||
            file_put_contents($publicKeyPath, $keys['publicKey']) === false) {
            throw new LetsEncryptClientException('Failed to write keys to files.');
        }

        return true;
    }

    protected function getKey(string $type): string
    {
        $filePath = $this->accountKeysPath.$this->getKeyName($type);

        if (!file_exists($filePath)) {
            throw new LetsEncryptClientException(sprintf('[%s] File does not exist', $filePath));
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new LetsEncryptClientException(sprintf('[%s] Failed to get contents of the file', $filePath));
        }

        return $content;
    }

    private function getKeyName(string $type): string
    {
        if (empty($this->accountName)) {
            throw new LetsEncryptClientException('Account name is not set.');
        }

        return sprintf('%s-%s.pem', $this->accountName, $type);
    }
}
