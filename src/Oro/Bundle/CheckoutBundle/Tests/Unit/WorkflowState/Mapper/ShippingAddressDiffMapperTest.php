<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingAddressDiffMapper;

class ShippingAddressDiffMapperTest extends AbstractAddressDiffMapperTest
{
    public function testGetName()
    {
        $this->assertEquals('shipping_address', $this->mapper->getName());
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
