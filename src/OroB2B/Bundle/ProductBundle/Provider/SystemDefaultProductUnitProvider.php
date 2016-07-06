<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class SystemDefaultProductUnitProvider extends AbstractDefaultProductUnitProvider
{
    /**
     * @return ProductUnitPrecision
     */
    public function getDefaultProductUnitPrecision()
    {
        $defaultUnitValue = $this->configManager->get('orob2b_product.default_unit');
        $defaultUnitPrecision = $this->configManager->get('orob2b_product.default_unit_precision');

        $unit = $this
            ->getRepository('OroB2BProductBundle:ProductUnit')->findOneBy(['code' => $defaultUnitValue]);

        return $this->createProductUnitPrecision($unit, $defaultUnitPrecision);
    }
}
