<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Provides the system-configured default product unit precision.
 *
 * This provider retrieves the default product unit from system configuration, creating a ProductUnitPrecision instance
 * with the configured unit and its default precision value.
 */
class SystemDefaultProductUnitProvider implements DefaultProductUnitProviderInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(ConfigManager $configManager, DoctrineHelper $doctrineHelper)
    {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return ProductUnitPrecision|null
     */
    #[\Override]
    public function getDefaultProductUnitPrecision()
    {
        $defaultUnitCode = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_UNIT));
        $defaultPrecision = (int)$this->configManager->get('oro_product.default_unit_precision');
        $unit = $this->doctrineHelper->getEntityReference(ProductUnit::class, $defaultUnitCode);
        return (new ProductUnitPrecision())->setUnit($unit)->setPrecision($defaultPrecision);
    }
}
