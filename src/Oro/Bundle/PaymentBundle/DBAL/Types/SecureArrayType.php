<?php

namespace Oro\Bundle\PaymentBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonArrayType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Class adds secure_array type to Doctrine Mapping Types
 * This type provides ability to transparently encrypt/decrypt json array in DB
 */
class SecureArrayType extends JsonArrayType
{
    const TYPE = 'secure_array';

    /** @var SymmetricCrypterInterface */
    private $crypter;

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        $value = parent::convertToDatabaseValue($value, $platform);

        return $this->getCrypter()->encryptData($value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return [];
        }

        $value = $this->getCrypter()->decryptData($value);

        return parent::convertToPHPValue($value, $platform);
    }

    /** {@inheritdoc} */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    /** {@inheritdoc} */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::TYPE;
    }

    public function setCrypter(SymmetricCrypterInterface $crypter)
    {
        $this->crypter = $crypter;
    }

    /**
     * @return SymmetricCrypterInterface
     */
    public function getCrypter()
    {
        if (!$this->crypter) {
            throw new \RuntimeException('Crypter is not set');
        }

        return $this->crypter;
    }
}
