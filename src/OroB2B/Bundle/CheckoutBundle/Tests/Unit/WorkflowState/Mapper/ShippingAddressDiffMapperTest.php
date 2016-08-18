<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingAddressDiffMapper;

class ShippingAddressDiffMapperTest extends AbstractAddressDiffMapperTest
{
    public function testGetName()
    {
        $this->assertEquals('shippingAddress', $this->mapper->getName());
    }

    /** {@inheritdoc} */
    protected function getMapper()
    {
        return new ShippingAddressDiffMapper();
    }

    /** {@inheritdoc} */
    protected function getTestAddressFieldName()
    {
        return 'shippingAddress';
    }
}
