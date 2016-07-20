<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\PoNumberDiffMapper;

class PoNumberDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PoNumberDiffMapper
     */
    private $mapper;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkout;

    public function setUp()
    {
        $this->mapper = new PoNumberDiffMapper();
        $this->checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
    }

    public function tearDown()
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

    public function testGetName()
    {
        $this->assertEquals('poNumber', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout->method('getPoNumber')->willReturn('testPoNumber');

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals('testPoNumber', $result);
    }

    public function testIsStateActualTrue()
    {
        $this->checkout->method('getPoNumber')->willReturn('testPoNumber');
        $savedState = [
            'parameter1' => 10,
            'poNumber' => 'testPoNumber',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalse()
    {
        $this->checkout->method('getPoNumber')->willReturn('changedPoNumber');
        $savedState = [
            'parameter1' => 10,
            'poNumber' => 'testPoNumber',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $this->checkout->method('getPoNumber')->willReturn('testPoNumber');
        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->checkout->method('getPoNumber')->willReturn('testPoNumber');
        $savedState = [
            'parameter1' => 10,
            'poNumber' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
