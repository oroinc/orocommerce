<?php

namespace Oro\Bundle\UPSBundle\Client\Url\Provider;

/**
 * Interface for UPS Client URL Provider
 */
interface UpsClientUrlProviderInterface
{
    public function getUpsUrl(bool $isTestMode): string;
}
