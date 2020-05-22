<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\Type\LengthUnitSelectType;

class LengthUnitSelectTypeTest extends AbstractShippingOptionSelectTypeTest
{
    protected function setUp(): void
    {
        $this->configureFormatter();
        $this->configureProvider();

        $this->formType = new LengthUnitSelectType($this->provider, $this->formatter);
        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(LengthUnitSelectType::NAME, $this->formType->getBlockPrefix());
    }
}
