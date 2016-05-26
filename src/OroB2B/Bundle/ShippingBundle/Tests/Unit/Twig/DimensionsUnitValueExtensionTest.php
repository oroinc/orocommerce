<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Twig;

use OroB2B\Bundle\ProductBundle\Tests\Unit\Twig\UnitValueExtensionTestCase;
use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Twig\DimensionsUnitValueExtension;

class DimensionsUnitValueExtensionTest extends UnitValueExtensionTestCase
{
    public function testGetName()
    {
        $this->assertEquals(DimensionsUnitValueExtension::NAME, $this->getExtension()->getName());
    }

    /**
     * @return DimensionsUnitValueExtension
     */
    protected function getExtension()
    {
        return new DimensionsUnitValueExtension($this->formatter);
    }

    /**
     * {@inheritdoc}
     */
    protected function createObject($code)
    {
        $unit = new LengthUnit();
        $unit->setCode($code);

        return $unit;
    }
}
