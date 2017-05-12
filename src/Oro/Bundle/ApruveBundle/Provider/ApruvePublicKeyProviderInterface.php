<?php

namespace Oro\Bundle\ApruveBundle\Provider;

interface ApruvePublicKeyProviderInterface
{
    /**
     * @return string
     */
    public function getPublicKey();
}
