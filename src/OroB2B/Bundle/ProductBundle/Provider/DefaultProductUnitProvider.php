<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class DefaultProductUnitProvider
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var ObjectManager |\PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;


    /**
     * DefaultProductUnitProvider constructor.
     * @param ConfigManager $configManager
     * @param ObjectManager $entityManager
     */
    public function __construct(ConfigManager $configManager, ObjectManager $entityManager)
    {
        $this->configManager = $configManager;
        $this->entityManager = $entityManager;
    }
    
    /**
     * @return ProductUnitPrecision $unitPrecision
     */
    public function getDefaultProductUnitPrecision()
    {
        $defaultUnitValue = $this->configManager->get('orob2b_product.default_unit');
        $defaultUnitPrecision = $this->configManager->get('orob2b_product.default_unit_precision');

        $unit = $this->entityManager
            ->getRepository('OroB2BProductBundle:ProductUnit')->findOneBy(['code' => $defaultUnitValue]);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setUnit($unit)
            ->setPrecision($defaultUnitPrecision);

        return $unitPrecision;
    }
}

