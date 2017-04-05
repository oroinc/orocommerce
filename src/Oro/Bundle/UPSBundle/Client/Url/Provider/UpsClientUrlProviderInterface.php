<?php

namespace Oro\Bundle\UPSBundle\Client\Url\Provider;

interface UpsClientUrlProviderInterface
{
    /**
     * @param bool $isTestMode
     *
     * @return string
     */
    public function getUpsUrl($isTestMode);
}
