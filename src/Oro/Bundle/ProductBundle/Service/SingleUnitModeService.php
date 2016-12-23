<?php

namespace Oro\Bundle\ProductBundle\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;

class SingleUnitModeService
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var DefaultProductUnitProviderInterface
     */
    private $defaultProductUnitProvider;

    /**
     * @param ConfigManager $configManager
     * @param DefaultProductUnitProviderInterface $defaultProductUnitProvider
     */
    public function __construct(
        ConfigManager $configManager,
        DefaultProductUnitProviderInterface $defaultProductUnitProvider
    ) {
        $this->configManager = $configManager;
        $this->defaultProductUnitProvider = $defaultProductUnitProvider;
    }

    /**
     * @return bool
     */
    public function isSingleUnitMode()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::SINGLE_UNIT_MODE));
    }

    /**
     * @return bool
     */
    public function isSingleUnitModeCodeVisible()
    {
        if (!$this->isSingleUnitMode()) {
            return true;
        }
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::SINGLE_UNIT_MODE_SHOW_CODE));
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isProductPrimaryUnitDefault(Product $product)
    {
        $defaultUnit = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_UNIT));
        return $product->getPrimaryUnitPrecision()->getUnit()->getCode() === $defaultUnit;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isProductPrimaryUnitSingleAndDefault(Product $product)
    {
        return $this->isProductPrimaryUnitDefault($product)
            && $product->getAdditionalUnitPrecisions()->isEmpty();
    }

    /**
     * @return null|ProductUnit
     */
    public function getConfigDefaultUnit()
    {
        $defaultUnitPrecision = $this->defaultProductUnitProvider->getDefaultProductUnitPrecision();
        if ($defaultUnitPrecision) {
            return $defaultUnitPrecision->getUnit();
        }
        return null;
    }

    /**
     * @param string $unitCode
     *
     * @return bool
     */
    public function isDefaultPrimaryUnit($unitCode)
    {
        return $this->getConfigDefaultUnit() === $unitCode;
    }
}
