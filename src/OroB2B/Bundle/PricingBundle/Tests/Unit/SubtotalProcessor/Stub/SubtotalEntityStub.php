<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

class SubtotalEntityStub extends EntityStub implements SubtotalAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSubtotal()
    {
        return 777;
    }
}
