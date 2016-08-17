<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;

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
        $this->checkout = $this->getMockBuilder('OroB2B\Bundle\CheckoutBundle\Entity\Checkout')->getMock();
    }

    protected function tearDown()
    {
        unset($this->mapper, $this->checkout);
    }

    public function testIsEntitySupported()
    {
        $this->assertTrue($this->mapper->isEntitySupported($this->checkout));
    }

    public function testIsEntitySupportedNotObject()
    {
        $entity = 'string';

        $this->assertFalse($this->mapper->isEntitySupported($entity));
    }

    public function testIsEntitySupportedUnsupportedEntity()
    {
        $entity = new \stdClass();

        $this->assertFalse($this->mapper->isEntitySupported($entity));
    }
}
