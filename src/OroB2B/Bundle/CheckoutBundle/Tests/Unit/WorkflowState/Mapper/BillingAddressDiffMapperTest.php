<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\BillingAddressDiffMapper;

class BillingAddressDiffMapperTest extends AbstractAddressDiffMapperTest
{
    public function testGetName()
    {
        $this->assertEquals('billingAddress', $this->mapper->getName());
    }

    /** {@inheritdoc} */
    protected function getMapper()
    {
        return new BillingAddressDiffMapper();
    }

    /** {@inheritdoc} */
    protected function getTestAddressFieldName()
    {
        return 'billingAddress';
    }
}
