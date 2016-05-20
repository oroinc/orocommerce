<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Model\Condition;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Model\Condition\OrderLineItemsHasCount;

class OrderLineItemsHasCountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderLineItemsHasCount
     */
    protected $condition;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    public function setUp()
    {
        $this->manager = $this
            ->getMockBuilder('OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager')
            ->disableOriginalConstructor()->getMock();

        $this->condition = new OrderLineItemsHasCount($this->manager);
    }

    public function testGetName()
    {
        $this->assertEquals(OrderLineItemsHasCount::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider initializeDataProvider
     * @param array $options
     * @param $message
     */
    public function testInitializeExceptions(array $options, $message)
    {
        $this->setExpectedException('Oro\Component\ConfigExpression\Exception\InvalidArgumentException', $message);
        $this->condition->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeDataProvider()
    {
        return [
            [
                'options' => [1, 2, 3],
                'exceptionMessage' => 'Options must have 1 elements, but 3 given.',
            ],
            [
                'options' => [],
                'exceptionMessage' => 'Options must have 1 elements, but 0 given.',
            ],
            [
                'options' => [1 => 1],
                'exceptionMessage' => 'Option "entity" must be set.',
            ]
        ];
    }

    public function testEvaluateException()
    {
        $this->setExpectedException(
            'Oro\Component\ConfigExpression\Exception\InvalidArgumentException',
            'Entity must implement OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface'
        );
        $context = [];
        $this->condition->initialize(['entity' => []]);
        $this->condition->evaluate($context);
    }

    /**
     * @dataProvider evaluateDataProvider
     * @param array $lineItems
     * @param $expectedResult
     */
    public function testEvaluate(array $lineItems, $expectedResult)
    {
        /** @var CheckoutInterface|\PHPUnit_Framework_MockObject_MockObject $checkout */
        $checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface');
        $context = [];
        $this->condition->initialize(['entity' => $checkout]);
        $this->manager->expects($this->once())
            ->method('getData')
            ->willReturn($lineItems);
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            [
                'lineItems' => [
                    $this->getMock('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem'),
                ],
                'expectedResult' => true,
            ],
            [
                'lineItems' => [],
                'expectedResult' => false,
            ]
        ];
    }
}
