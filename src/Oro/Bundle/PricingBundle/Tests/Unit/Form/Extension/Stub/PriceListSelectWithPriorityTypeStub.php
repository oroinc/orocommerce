<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\AbstractType;

use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;

class PriceListSelectWithPriorityTypeStub extends AbstractType
{
    /**
     * @return string
     */
    public function getName()
    {
        return PriceListSelectWithPriorityType::NAME;
    }
}
