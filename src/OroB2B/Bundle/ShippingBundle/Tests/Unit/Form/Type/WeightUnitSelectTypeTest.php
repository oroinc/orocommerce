<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ShippingBundle\Form\Type\WeightUnitSelectType;

class WeightUnitSelectTypeTest extends AbstractShippingOptionSelectTypeTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new WeightUnitSelectType($this->provider, $this->formatter);
    }

    public function testGetName()
    {
        $this->assertEquals(WeightUnitSelectType::NAME, $this->formType->getName());
    }
}
