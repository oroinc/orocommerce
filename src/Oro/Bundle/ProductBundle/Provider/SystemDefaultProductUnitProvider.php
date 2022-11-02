<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

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
    public function getDefaultProductUnitPrecision()
    {
        $defaultUnitCode = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_UNIT));
        $defaultPrecision = (int)$this->configManager->get('oro_product.default_unit_precision');
        $unit = $this->doctrineHelper->getEntityReference(ProductUnit::class, $defaultUnitCode);
        return (new ProductUnitPrecision())->setUnit($unit)->setPrecision($defaultPrecision);
    }
}
