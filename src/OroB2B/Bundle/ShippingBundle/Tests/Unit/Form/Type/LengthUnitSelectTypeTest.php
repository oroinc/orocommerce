<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ShippingBundle\Form\Type\LengthUnitSelectType;

class LengthUnitSelectTypeTest extends AbstractShippingOptionSelectTypeTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new LengthUnitSelectType($this->repository, $this->configManager, $this->formatter);
    }

    public function testGetName()
    {
        $this->assertEquals(LengthUnitSelectType::NAME, $this->formType->getName());
    }
}
