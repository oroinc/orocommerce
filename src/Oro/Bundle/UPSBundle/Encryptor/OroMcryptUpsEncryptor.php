<?php

namespace Oro\Bundle\UPSBundle\Encryptor;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class OroMcryptUpsEncryptor implements UpsEncryptorInterface
{
    /**
     * @var Mcrypt
     */
    private $oroSecurityEncoderMcrypt;

    /**
     * OroMcryptUpsEncryptor constructor.
     *
     * @param Mcrypt $oroMcryptUpsEncryptor
     */
    public function __construct(Mcrypt $oroMcryptUpsEncryptor)
    {
        $this->oroSecurityEncoderMcrypt = $oroMcryptUpsEncryptor;
    }

    /**
     * @inheritDoc
     */
    public function encrypt($data)
    {
        return $this->oroSecurityEncoderMcrypt->encryptData($data);
    }

    /**
     * @inheritDoc
     */
    public function decrypt($data)
    {
        return $this->oroSecurityEncoderMcrypt->decryptData($data);
    }
}
