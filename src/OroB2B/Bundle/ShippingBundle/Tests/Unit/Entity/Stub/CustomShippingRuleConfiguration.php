<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity\Stub;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;

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
