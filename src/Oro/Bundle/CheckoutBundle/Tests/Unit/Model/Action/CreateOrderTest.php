<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Model\Action\CreateOrder;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateOrderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mapper;

    /** @var EntityPaymentMethodsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodsProvider;

    /** @var CreateOrder */
    private $action;

    protected function setUp(): void
    {
        $this->mapper = $this->createMock(MapperInterface::class);
        $this->paymentMethodsProvider = $this->createMock(EntityPaymentMethodsProvider::class);

        $this->action = new CreateOrder(
            new ContextAccessor(),
            $this->mapper,
            $this->paymentMethodsProvider
        );
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitialize()
    {
        $options = [
            CreateOrder::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
            CreateOrder::OPTION_KEY_ATTRIBUTE => new PropertyPath('order'),
            CreateOrder::OPTION_KEY_DATA => ['billingAddress' => new PropertyPath('billingAddress')],
        ];

        self::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exception, string $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
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
        $checkout->setPaymentMethod('pm1');
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

        $this->paymentMethodsProvider->expects($this->once())
            ->method('storePaymentMethodsToEntity')
            ->with($expected, ['pm1']);

        $this->action->execute($context);

        $this->assertEquals($expected, $context['order']);
    }
}
