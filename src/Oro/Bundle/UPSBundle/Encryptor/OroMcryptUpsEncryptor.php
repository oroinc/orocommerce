<?php

namespace Oro\Bundle\UPSBundle\Encryptor;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class OroMcryptUpsEncryptor implements UpsEncryptorInterface
{
    /**
     * @var Mcrypt
     */
    private $oroMcryptUpsEncryptor;

    /**
     * OroMcryptUpsEncryptor constructor.
     *
     * @param Mcrypt $oroMcryptUpsEncryptor
     */
    public function __construct(Mcrypt $oroMcryptUpsEncryptor)
    {
        $this->oroMcryptUpsEncryptor = $oroMcryptUpsEncryptor;
    }

    /**
     * @inheritDoc
     */
    public function encrypt($data)
    {
        return $this->oroMcryptUpsEncryptor->encryptData($data);
    }

    /**
     * @inheritDoc
     */
    public function decrypt($data)
    {
        return $this->oroMcryptUpsEncryptor->decryptData($data);
    }
}
