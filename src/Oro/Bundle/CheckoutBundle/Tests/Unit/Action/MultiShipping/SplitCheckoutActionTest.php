<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\SplitCheckoutAction;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class SplitCheckoutActionTest extends TestCase
{
    private CheckoutSplitter|MockObject $checkoutSplitter;
    private ContextAccessor|MockObject $contextAccessor;
    private EventDispatcher|MockObject $dispatcher;
    private GroupedCheckoutLineItemsProvider $groupedLineItemsProvider;
    private SplitCheckoutAction $action;

    protected function setUp(): void
    {
        $this->checkoutSplitter = $this->createMock(CheckoutSplitter::class);
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->groupedLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);

        $this->action = new SplitCheckoutAction(
            $this->contextAccessor,
            $this->checkoutSplitter,
            $this->groupedLineItemsProvider
        );
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testExecuteAction()
    {
        $checkout = new Checkout();
        $lineItem1 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem1, 1);

        $lineItem2 = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem2, 2);

        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2]));

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->will($this->onConsecutiveCalls($checkout, [
                'product.owner:1' => [1],
                'product.owner:2' => [2]
            ]));

        $groupedLineItems = [
            'product.owner:1' => [0 => $lineItem1],
            'product.owner:2' => [1 => $lineItem2]
        ];

        $this->groupedLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsByIds')
            ->willReturn($groupedLineItems);

        $this->checkoutSplitter->expects($this->once())
            ->method('split')
            ->with(
                $checkout,
                $groupedLineItems
            );

        $this->contextAccessor->expects($this->once())
            ->method('setValue');

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $context = new \stdClass();
        $context->checkout = $checkout;

        $checkoutOption = new PropertyPath('checkout');
        $groupedLineItemsOption = new PropertyPath('groupedLineItems');
        $attributeOption = new PropertyPath('attribute');

        $this->action->initialize([
            'checkout' => $checkoutOption,
            'groupedLineItems' => $groupedLineItemsOption,
            'attribute' => $attributeOption
        ]);
        $this->action->execute($context);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInitializeException(array $options, $exceptionMessage): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            'Empty options' => [
                [],
                '"attribute" parameter is required'
            ],
            'Absent "attribute" option ' => [
                [
                    'checkout' => new PropertyPath('checkout'),
                    'groupedLineItems' => new PropertyPath('groupedLineItems'),
                ],
                '"attribute" parameter is required'
            ],
            'Absent "checkout" option' =>
            [
                [
                    'groupedLineItems' => new PropertyPath('groupedLineItems'),
                    'attribute' => new PropertyPath('attribute')
                ],
                '"checkout" parameter is required'
            ],
            'Absent "groupedLineItems" option' => [
                [
                    'checkout' => new PropertyPath('checkout'),
                    'attribute' => new PropertyPath('attribute')
                ],
                '"groupedLineItems" parameter is required'
            ],
        ];
    }
}
