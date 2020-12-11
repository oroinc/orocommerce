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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetOrderLineItemsTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor */
    private $contextAccessor;

    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var GetOrderLineItems */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new GetOrderLineItems($this->contextAccessor, $this->checkoutLineItemsManager);
        $this->action->setDispatcher($eventDispatcher);
    }

    public function testInitialize(): void
    {
        $options = [
            GetOrderLineItems::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
            GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems',
        ];

        $this->assertInstanceOf(ActionInterface::class, $this->action->initialize($options));

        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     *
     * @param array $options
     * @param string $exception
     * @param string $exceptionMessage
     */
    public function testInitializeException(array $options, $exception, $exceptionMessage): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider(): array
    {
        return [
            [
                'options' => [
                    GetOrderLineItems::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Attribute name parameter is required',
            ],
            [
                'options' => [
                    GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems',
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Checkout name parameter is required',
            ],
            [
                'options' => [
                    GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems',
                    'config_visibility_path' => 'sample_path',
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Checkout name parameter is required',
            ],
        ];
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $contextData
     * @param array $options
     * @param array $expectedArguments
     */
    public function testExecute(array $contextData, array $options, array $expectedArguments): void
    {
        $context = new ActionData($contextData);
        $this->action->initialize($options);

        $orderLineItems = new ArrayCollection([new OrderLineItem()]);
        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with(...$expectedArguments)
            ->willReturn($orderLineItems);

        $this->action->execute($context);

        $this->assertEquals($orderLineItems, $context['lineItems']);
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        $checkout = new Checkout();
        $configVisibilityPath = 'sample_path';

        return [
            [
                'contextData' => ['checkout' => $checkout],
                'options' => [
                    GetOrderLineItems::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
                    GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems',
                ],
                'arguments' => [$checkout],
            ],
            [
                'contextData' => ['checkout' => $checkout],
                'options' => [
                    GetOrderLineItems::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
                    GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems',
                    'disable_price_filter' => false,
                ],
                'arguments' => [$checkout, false],
            ],
            [
                'contextData' => ['checkout' => $checkout],
                'options' => [
                    GetOrderLineItems::OPTION_KEY_CHECKOUT => new PropertyPath('checkout'),
                    GetOrderLineItems::OPTION_KEY_ATTRIBUTE => 'lineItems',
                    'disable_price_filter' => true,
                    'config_visibility_path' => $configVisibilityPath,
                ],
                'arguments' => [$checkout, true, $configVisibilityPath],
            ],
        ];
    }
}
