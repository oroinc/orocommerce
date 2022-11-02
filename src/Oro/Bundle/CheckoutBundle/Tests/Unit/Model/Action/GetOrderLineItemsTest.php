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
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class GetOrderLineItemsTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var GetOrderLineItems */
    private $action;

    protected function setUp(): void
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);

        $this->action = new GetOrderLineItems(new ContextAccessor(), $this->checkoutLineItemsManager);
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitialize(): void
    {
        $options = [
            'checkout' => new PropertyPath('checkout'),
            'attribute' => 'lineItems',
        ];

        $this->assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        $this->assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exception, string $exceptionMessage): void
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
                    'checkout' => new PropertyPath('checkout'),
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Attribute name parameter is required',
            ],
            [
                'options' => [
                    'attribute' => 'lineItems',
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Checkout name parameter is required',
            ],
            [
                'options' => [
                    'attribute' => 'lineItems',
                    'config_visibility_path' => 'sample_path',
                ],
                'expectedException' => InvalidParameterException::class,
                'expectedExceptionMessage' => 'Checkout name parameter is required',
            ],
        ];
    }

    /**
     * @dataProvider executeDataProvider
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

    public function executeDataProvider(): array
    {
        $checkout = new Checkout();
        $configVisibilityPath = 'sample_path';

        return [
            [
                'contextData' => ['checkout' => $checkout],
                'options' => [
                    'checkout' => new PropertyPath('checkout'),
                    'attribute' => 'lineItems',
                ],
                'arguments' => [$checkout],
            ],
            [
                'contextData' => ['checkout' => $checkout],
                'options' => [
                    'checkout' => new PropertyPath('checkout'),
                    'attribute' => 'lineItems',
                    'disable_price_filter' => false,
                ],
                'arguments' => [$checkout, false],
            ],
            [
                'contextData' => ['checkout' => $checkout],
                'options' => [
                    'checkout' => new PropertyPath('checkout'),
                    'attribute' => 'lineItems',
                    'disable_price_filter' => true,
                    'config_visibility_path' => $configVisibilityPath,
                ],
                'arguments' => [$checkout, true, $configVisibilityPath],
            ],
        ];
    }
}
