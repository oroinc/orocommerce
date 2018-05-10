<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use Symfony\Component\Form\AbstractType;

class PriceListSelectWithPriorityTypeStub extends AbstractType
{
    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return PriceListSelectWithPriorityType::NAME;
    }
}
