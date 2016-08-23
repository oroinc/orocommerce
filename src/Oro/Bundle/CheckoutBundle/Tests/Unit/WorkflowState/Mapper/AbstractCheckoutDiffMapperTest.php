<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;

abstract class AbstractCheckoutDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutStateDiffMapperInterface
     */
    protected $mapper;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkout;

    protected function setUp()
    {
        $this->checkout = $this->getMockBuilder('Oro\Bundle\CheckoutBundle\Entity\Checkout')->getMock();
    }

    protected function tearDown()
    {
        unset($this->mapper, $this->checkout);
    }

    public function testIsEntitySupported()
    {
        $this->assertEquals(true, $this->mapper->isEntitySupported($this->checkout));
    }

    public function testIsEntitySupportedNotObject()
    {
        $entity = 'string';

        $this->assertEquals(false, $this->mapper->isEntitySupported($entity));
    }

    public function testIsEntitySupportedUnsupportedEntity()
    {
        $entity = new \stdClass();

        $this->assertEquals(false, $this->mapper->isEntitySupported($entity));
    }
}
