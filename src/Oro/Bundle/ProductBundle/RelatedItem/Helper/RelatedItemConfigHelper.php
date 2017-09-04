<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\Helper;

use Oro\Bundle\ProductBundle\Exception\ConfigProviderNotFoundException;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;

class RelatedItemConfigHelper
{
    const RELATED_ITEMS_TRANSLATION_NAMESPACE = 'oro.product.sections';
    const RELATED_ITEMS_TRANSLATION_DEFAULT = 'related_items';

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
     * @throws ConfigProviderNotFoundException
     */
    public function getConfigProvider($providerName)
    {
        if (!isset($this->configProviders[$providerName])) {
            throw ConfigProviderNotFoundException::fromString($providerName);
        }

        return $this->configProviders[$providerName];
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

    /**
     * @return string
     */
    public function getRelatedItemsTranslationKey()
    {
        $enabled = [];
        $suffix = self::RELATED_ITEMS_TRANSLATION_DEFAULT;

        foreach ($this->configProviders as $configKey => $configProvider) {
            if ($configProvider->isEnabled()) {
                array_push($enabled, $configKey);
            }
        }

        if (count($enabled) === 1) {
            $suffix = $enabled[0];
        }

        return self::RELATED_ITEMS_TRANSLATION_NAMESPACE . '.' . $suffix;
    }
}
