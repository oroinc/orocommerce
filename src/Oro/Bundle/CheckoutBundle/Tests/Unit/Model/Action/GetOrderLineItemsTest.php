<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Model\Action\GetOrderLineItems;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class GetOrderLineItemsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutLineItemsManager;

    /**
     * @var GetOrderLineItems
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->checkoutLineItemsManager = $this
            ->getMockBuilder('Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher $eventDispatcher */
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->action = new GetOrderLineItems($this->contextAccessor, $this->checkoutLineItemsManager);
        $this->action->setDispatcher($eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->action);
    }

    public function testInitialize()
    {
        $options = [
            GetOrderLineItems::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
            GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems'
        ];

        $this->assertInstanceOf(
            'Oro\Component\Action\Action\ActionInterface',
            $this->action->initialize($options)
        );

        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array $options
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testInitializeException(array $options, $exception, $exceptionMessage)
    {
        $this->setExpectedException($exception, $exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            [
                'options' => [
                    GetOrderLineItems::OPTION_KEY_CHECKOUT => new PropertyPath('checkout')
                ],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Attribute name parameter is required',
            ],
            [
                'options' => [
                    GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems'
                ],
                'expectedException' => 'Oro\Component\Action\Exception\InvalidParameterException',
                'expectedExceptionMessage' => 'Checkout name parameter is required',
            ],
        ];
    }

    /**
     * @dataProvider executeDataProvider
     * @param ArrayCollection $expected
     */
    public function testExecute(ArrayCollection $expected)
    {
        $checkout = new Checkout();
        $context = new ActionData(['checkout' => $checkout]);

        $this->action->initialize([
            GetOrderLineItems::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
            GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems'
        ]);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($expected);

        $this->action->execute($context);

        $this->assertEquals($expected, $context['lineItems']);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'expected' => new ArrayCollection([
                    new OrderLineItem()
                ])
            ]
        ];
    }
}
