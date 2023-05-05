<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupedLineItemsProviderInterface;
use Oro\Component\Testing\ReflectionUtil;

class GroupedCheckoutLineItemsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var GroupedLineItemsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupingService;

    /** @var CheckoutLineItemsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsProvider;

    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var GroupedCheckoutLineItemsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->groupingService = $this->createMock(GroupedLineItemsProviderInterface::class);
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);

        $this->provider = new GroupedCheckoutLineItemsProvider(
            $this->groupingService,
            $this->checkoutLineItemsProvider,
            $this->checkoutFactory
        );
    }

    private function getCheckout(array $lineItems): Checkout
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection($lineItems));

        return $checkout;
    }

    private function getCheckoutLineItem(int $id, ?string $sku = null, ?string $unitCode = null): CheckoutLineItem
    {
        $lineItem = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem, $id);
        if (null !== $sku) {
            $lineItem->setProductSku($sku);
        }
        if (null !== $unitCode) {
            $lineItem->setProductUnitCode($unitCode);
        }

        return $lineItem;
    }

    public function testGetGroupedLineItems()
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem(1);
        $checkoutLineItem2 = $this->getCheckoutLineItem(2);
        $checkoutLineItem3 = $this->getCheckoutLineItem(3);
        $checkoutLineItem4 = $this->getCheckoutLineItem(4);

        $checkoutSource = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem4
        ]);

        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkoutSource)
            ->willReturn(new ArrayCollection([$checkoutLineItem1, $checkoutLineItem2, $checkoutLineItem3]));

        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3
        ]));

        $this->checkoutFactory->expects($this->once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $groupedLineItems = [
            'product.owner:1' => [$checkoutLineItem1, $checkoutLineItem3],
            'product.owner:2' => [$checkoutLineItem2]
        ];

        $this->groupingService->expects($this->once())
            ->method('getGroupedLineItems')
            ->with($checkout)
            ->willReturn($groupedLineItems);

        $result = $this->provider->getGroupedLineItems($checkoutSource);
        $this->assertEquals($groupedLineItems, $result);
    }

    public function testGetGroupedLineItemsIds()
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem(1, 'sku-1', 'item');
        $checkoutLineItem2 = $this->getCheckoutLineItem(2, 'sku-2', 'set');
        $checkoutLineItem3 = $this->getCheckoutLineItem(3, 'sku-3', 'item');
        $checkoutLineItem4 = $this->getCheckoutLineItem(4, 'sku-4', 'set');

        $checkoutSource = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem4
        ]);

        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkoutSource)
            ->willReturn(new ArrayCollection([$checkoutLineItem1, $checkoutLineItem2, $checkoutLineItem3]));

        $checkout = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3
        ]);

        $this->checkoutFactory->expects($this->once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $groupedLineItems = [
            'product.owner:1' => [$checkoutLineItem1, $checkoutLineItem3],
            'product.owner:2' => [$checkoutLineItem2]
        ];

        $this->groupingService->expects($this->once())
            ->method('getGroupedLineItems')
            ->with($checkout)
            ->willReturn($groupedLineItems);

        $result = $this->provider->getGroupedLineItemsIds($checkoutSource);
        $this->assertEquals(
            [
                'product.owner:1' => ['sku-1:item', 'sku-3:item'],
                'product.owner:2' => ['sku-2:set']
            ],
            $result
        );
    }

    public function testGetGroupedLineItemsByIds()
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem(1, 'sku-1', 'item');
        $checkoutLineItem2 = $this->getCheckoutLineItem(2, 'sku-2', 'set');
        $checkoutLineItem3 = $this->getCheckoutLineItem(3, 'sku-3', 'item');
        $checkoutLineItem4 = $this->getCheckoutLineItem(4, 'sku-4', 'set');

        $checkout = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem4
        ]);

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item', 'sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $result = $this->provider->getGroupedLineItemsByIds($checkout, $groupedLineItemsIds);
        $this->assertEquals(
            [
                'product.owner:1' => [$checkoutLineItem1, $checkoutLineItem3],
                'product.owner:2' => [$checkoutLineItem2]
            ],
            $result
        );
    }
}
