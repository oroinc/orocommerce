<?php

namespace Oro\Bundle\PaymentBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonArrayType;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class SecureArrayType extends JsonArrayType
{
    const TYPE = 'secure_array';

    /** @var Mcrypt */
    private $mcrypt;

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        $value = parent::convertToDatabaseValue($value, $platform);

        return $this->getMcrypt()->encryptData($value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return [];
        }

        $value = $this->getMcrypt()->decryptData($value);

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

    /**
     * @param Mcrypt $mcrypt
     */
    public function setMcrypt(Mcrypt $mcrypt)
    {
        $this->mcrypt = $mcrypt;
    }

    /**
     * @return Mcrypt
     */
    public function getMcrypt()
    {
        if (!$this->mcrypt) {
            throw new \RuntimeException('Mcrypt missing');
        }

        return $this->mcrypt;
    }
}
