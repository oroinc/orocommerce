<?php

namespace Oro\Bundle\UPSBundle\Encryptor;

interface UpsEncryptorInterface
{
    /**
     * @param string $data
     *
     * @return string
     */
    public function encrypt($data);

    /**
     * @param string $data
     *
     * @return string
     */
    public function decrypt($data);
}
