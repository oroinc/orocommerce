<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class SystemDefaultProductUnitProvider implements DefaultProductUnitProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ConfigManager $configManager
     * @param ManagerRegistry $registry
     */
    public function __construct(ConfigManager $configManager, ManagerRegistry $registry)
    {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }
    
    /**
     * @return ProductUnitPrecision
     */
    public function getDefaultProductUnitPrecision()
    {
        $defaultUnitValue = $this->configManager->get('orob2b_product.default_unit');
        $defaultUnitPrecision = (int)$this->configManager->get('orob2b_product.default_unit_precision');

        $unit = $this->registry
            ->getManagerForClass('OroB2BProductBundle:ProductUnit')
            ->getRepository('OroB2BProductBundle:ProductUnit')
            ->findOneBy(['code' => $defaultUnitValue]);
        if ($unit instanceof ProductUnit) {
            $productUnitPrecision = new ProductUnitPrecision();
            return $productUnitPrecision->setUnit($unit)->setPrecision($defaultUnitPrecision);
        } else {
            return null;
        }
    }
}
