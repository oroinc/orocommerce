<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Model\Action\CreateOrder;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

class CreateOrderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var MapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mapper;

    /**
     * @var CreateOrder
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->mapper = $this->getMock(MapperInterface::class);

        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher */
        $eventDispatcher = $this
            ->getMockBuilder(EventDispatcherInterface::class)
            ->getMock();

        $this->action = new CreateOrder($this->contextAccessor, $this->mapper);
        $this->action->setDispatcher($eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->action);
    }

    public function testInitialize()
    {
        $options = [
            CreateOrder::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
            CreateOrder::OPTION_KEY_ATTRIBUTE => new PropertyPath('order'),
            CreateOrder::OPTION_KEY_DATA => ['billingAddress' => new PropertyPath('billingAddress')],
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
                    CreateOrder::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Attribute name parameter is required',
            ],
            [
                'options' => [
                    CreateOrder::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
                    CreateOrder::OPTION_KEY_ATTRIBUTE => 'order',
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Attribute must be valid property definition',
            ],
            [
                'options' => [
                    CreateOrder::OPTION_KEY_ATTRIBUTE => new PropertyPath('order'),
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Checkout name parameter is required',
            ],
            [
                'options' => [
                    CreateOrder::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
                    CreateOrder::OPTION_KEY_ATTRIBUTE => new PropertyPath('order'),
                    CreateOrder::OPTION_KEY_DATA => new PropertyPath('order'),
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Object data must be an array',
            ],
        ];
    }

    public function testExecute()
    {
        $expected = new Order();
        $checkout = new Checkout();
        $data = [
            'lineItems' => new ArrayCollection([new PropertyPath('lineItems')]),
        ];
        $context = new ActionData(
            [
                'checkout' => $checkout,
                'data' => $data,
                'array_value' => [
                    'array' => 'value',
                ],
            ]
        );

        $this->action->initialize(
            [
                CreateOrder::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
                CreateOrder::OPTION_KEY_ATTRIBUTE => new PropertyPath('order'),
                CreateOrder::OPTION_KEY_DATA => $data,
            ]
        );

        $this->mapper->expects($this->once())
            ->method('map')
            ->with($checkout, $data)
            ->willReturn($expected);

        $this->action->execute($context);

        $this->assertEquals($expected, $context['order']);
    }
}
