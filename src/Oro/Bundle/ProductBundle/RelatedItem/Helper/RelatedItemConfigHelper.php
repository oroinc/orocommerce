<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\Helper;

use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;

class RelatedItemConfigHelper
{
    /** @var AbstractRelatedItemConfigProvider[] */
    private $configProviders = [];

    /**
     * @return AbstractRelatedItemConfigProvider[]
     */
    public function getConfigProviders()
    {
        return $this->configProviders;
    }

    /**
     * @param string $providerName
     *
     * @return AbstractRelatedItemConfigProvider
     */
    public function getConfigProvider($providerName)
    {
        return $this->configProviders[$providerName] ?? null;
    }

    /**
     * @param string                            $providerName
     * @param AbstractRelatedItemConfigProvider $configProvider
     *
     * @return RelatedItemConfigHelper
     */
    public function addConfigProvider($providerName, AbstractRelatedItemConfigProvider $configProvider)
    {
        $this->configProviders[$providerName] = $configProvider;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAnyEnabled()
    {
        $isAnyEnabled = false;

        foreach ($this->configProviders as $configProvider) {
            if ($configProvider->isEnabled()) {
                return true;
            }
        }

        return $isAnyEnabled;
    }
}
