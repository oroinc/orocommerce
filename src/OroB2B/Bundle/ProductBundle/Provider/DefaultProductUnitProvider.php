<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\CongifManager;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class DefaultProductUnitProvider
{
    private $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CongifManager $configManager */
    public function __construct(CongifManager $configManager)
    {
        $this->configManager = $configManager;
    }
    /**
     * @return ProductUnit $productUnit
     */
    public function getDefaultProductUnit()
    {
        $defaultUnitValue = $this->configManager->get('orob2b_product.default_unit');
        $defaultUnitPrecision = $this->configManager->get('orob2b_product.default_unit_precision');
        $productUnit = new ProductUnit();
        $productUnit->setCode($defaultUnitValue);
        $productUnit->setDefaultPrecision($defaultUnitPrecision);
        return $productUnit;
    }
}

