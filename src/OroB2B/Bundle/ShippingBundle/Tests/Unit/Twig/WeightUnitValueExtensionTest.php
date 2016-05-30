<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ProductBundle\Tests\Unit\Twig\UnitValueExtensionTestCase;

use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Twig\WeightUnitValueExtension;

class WeightUnitValueExtensionTest extends UnitValueExtensionTestCase
{
    public function testGetName()
    {
        $this->assertEquals(WeightUnitValueExtension::NAME, $this->getExtension()->getName());
    }

    /**
     * @return WeightUnitValueExtension
     */
    protected function getExtension()
    {
        return new WeightUnitValueExtension($this->formatter);
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($code)
    {
        $unit = new WeightUnit();
        $unit->setCode($code);

        return $unit;
    }
}
