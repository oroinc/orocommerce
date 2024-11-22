<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Model\Action\CreateOrder;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateOrderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mapper;

    /** @var EntityPaymentMethodsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodsProvider;

    /** @var CreateOrder */
    private $action;

    private $managerRegistry;

    protected function setUp(): void
    {
        $this->mapper = self::createMock(MapperInterface::class);
        $this->paymentMethodsProvider = self::createMock(EntityPaymentMethodsProvider::class);
        $this->managerRegistry = self::createMock(ManagerRegistry::class);

        $this->action = new CreateOrder(
            new ContextAccessor(),
            $this->mapper,
            $this->paymentMethodsProvider
        );
        $this->action->setManagerRegistry($this->managerRegistry);
        $this->action->setDispatcher(self::createMock(EventDispatcherInterface::class));
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
                    CreateOrder::OPTION_KEY_DATA => new \stdClass(),
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Object data must be an array',
            ],
        ];
    }

    public function testExecuteWithoutOrder(): void
    {
        $checkout = self::getEntity(Checkout::class, ['id' => 100]);
        $checkout->setPaymentMethod('pm1');
        $expected = new Order();
        $expected->setUuid($checkout->getUuid());
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

        $this->mapper->expects(self::once())
            ->method('map')
            ->with($checkout, $data)
            ->willReturn($expected);

        $this->paymentMethodsProvider->expects(self::once())
            ->method('storePaymentMethodsToEntity')
            ->with($expected, ['pm1']);

        $repository = self::createMock(OrderRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['uuid' => $checkout->getUuid()])
            ->willReturn(null);

        $manager = self::createMock(EntityManager::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->managerRegistry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($manager);

        $this->action->execute($context);

        /** @var Order $contextOrder */
        $contextOrder = $context['order'];

        self::assertEquals($expected, $contextOrder);
        self::assertEquals($checkout->getUuid(), $contextOrder->getUuid());
    }

    public function testExecuteWithOrder(): void
    {
        $checkout = self::getEntity(Checkout::class, ['id' => 10]);
        $checkout->setPaymentMethod('pm1');

        $expected = new Order();
        $expected->setUuid($checkout->getUuid());

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

        $this->mapper->expects(self::never())->method('map');

        $this->paymentMethodsProvider->expects(self::once())
            ->method('storePaymentMethodsToEntity')
            ->with($expected, ['pm1']);

        $repository = self::createMock(OrderRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['uuid' => $checkout->getUuid()])
            ->willReturn($expected);

        $manager = self::createMock(EntityManager::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->managerRegistry
            ->expects(self::once())
            ->method('getManager')
            ->willReturn($manager);

        $this->action->execute($context);

        /** @var Order $contextOrder */
        $contextOrder = $context['order'];

        self::assertEquals($expected, $contextOrder);
        self::assertEquals($checkout->getUuid(), $contextOrder->getUuid());
    }
}
