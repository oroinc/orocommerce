<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Tests\Unit\Twig\UnitValueExtensionTestCase;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Twig\WeightUnitValueExtension;

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
