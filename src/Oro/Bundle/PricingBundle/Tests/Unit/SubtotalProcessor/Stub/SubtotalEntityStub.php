<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

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
