<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\BillingAddressDiffMapper;

class BillingAddressDiffMapperTest extends AbstractAddressDiffMapperTest
{
    public function testGetName()
    {
        $this->assertEquals('billing_address', $this->mapper->getName());
    }

    #[\Override]
    protected function getMapper()
    {
        return new BillingAddressDiffMapper();
    }

    #[\Override]
    protected function getTestAddressFieldName()
    {
        return 'billingAddress';
    }
}
