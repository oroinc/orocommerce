<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

abstract class AbstractDefaultProductUnitProvider
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
    abstract public function getDefaultProductUnitPrecision();
    
    /**
     * @param string $className
     * @return EntityRepository
     */
    protected function getRepository($className)
    {
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className);
    }

    /**
     * @param ProductUnit $unit
     * @param int $precision
     * @return ProductUnitPrecision
     */
    protected function createProductUnitPrecision($unit, $precision)
    {
        $productUnitPrecision = new ProductUnitPrecision();
        return $productUnitPrecision->setUnit($unit)->setPrecision($precision);
    }
}
