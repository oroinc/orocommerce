<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;

class CustomShippingRuleConfiguration extends ShippingRuleConfiguration
{
    /**
     * @return string
     */
    public function __toString()
    {
        return 'Custom shipping method, type';
    }
}
