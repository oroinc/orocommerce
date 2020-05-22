<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Model\Action\GetOrderLineItems;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetOrderLineItemsTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var CheckoutLineItemsManager|MockObject */
    protected $checkoutLineItemsManager;

    /** @var GetOrderLineItems */
    protected $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->checkoutLineItemsManager = $this
            ->getMockBuilder(CheckoutLineItemsManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EventDispatcherInterface|MockObject $eventDispatcher $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new class($this->contextAccessor, $this->checkoutLineItemsManager) extends GetOrderLineItems {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };
        $this->action->setDispatcher($eventDispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->action);
    }

    public function testInitialize()
    {
        $options = [
            GetOrderLineItems::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
            GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems'
        ];

        static::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        static::assertEquals($options, $this->action->xgetOptions());
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
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
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
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Attribute name parameter is required',
            ],
            [
                'options' => [
                    GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems'
                ],
                'expectedException' => InvalidParameterException::class,
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

        $this->checkoutLineItemsManager->expects(static::once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($expected);

        $this->action->execute($context);

        static::assertEquals($expected, $context['lineItems']);
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
