<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ShippingBundle\Form\Type\FreightClassSelectType;

class FreightClassSelectTypeTest extends AbstractShippingOptionSelectTypeTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new FreightClassSelectType($this->provider, $this->formatter);
    }

    public function testGetName()
    {
        $this->assertEquals(FreightClassSelectType::NAME, $this->formType->getName());
    }
}
