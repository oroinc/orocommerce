<?php

namespace Oro\Bundle\ApruveBundle\Client\Url\Provider;

interface ApruveClientUrlProviderInterface
{
    /**
     * @param bool $isTestMode
     *
     * @return string
     */
    public function getApruveUrl($isTestMode);
}
