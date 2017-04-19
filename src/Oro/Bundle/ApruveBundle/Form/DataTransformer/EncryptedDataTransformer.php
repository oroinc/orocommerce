<?php

namespace Oro\Bundle\ApruveBundle\Form\DataTransformer;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Data transformer for security-sensitive data.
 */
class EncryptedDataTransformer implements DataTransformerInterface
{
    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    /**
     * @var bool
     */
    private $decrypt;

    /**
     * @param SymmetricCrypterInterface $crypter
     * @param bool $decrypt Decrypt data when showing to user or not. Preferably to set to "false" for passwords.
     */
    public function __construct(SymmetricCrypterInterface $crypter, $decrypt)
    {
        $this->crypter = $crypter;
        $this->decrypt = (bool) $decrypt;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        if ($this->decrypt === true) {
            try {
                return $this->crypter->decryptData($value);
            } catch (\Exception $e) {
                // Decryption failure.
                return null;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        return $this->crypter->encryptData($value);
    }
}
