<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Tests\Unit\Twig\UnitValueExtensionTestCase;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Twig\DimensionsUnitValueExtension;

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
