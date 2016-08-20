<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\Type\LengthUnitSelectType;

class LengthUnitSelectTypeTest extends AbstractShippingOptionSelectTypeTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new LengthUnitSelectType($this->provider, $this->formatter);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LengthUnitSelectType::NAME, $this->formType->getBlockPrefix());
    }
}
