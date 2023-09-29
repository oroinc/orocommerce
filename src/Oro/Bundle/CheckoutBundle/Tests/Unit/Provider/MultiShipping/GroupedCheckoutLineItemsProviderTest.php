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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupedCheckoutLineItemsProviderTest extends TestCase
{
    private GroupedLineItemsProviderInterface|MockObject $groupingService;

    private CheckoutLineItemsProvider|MockObject $checkoutLineItemsProvider;

    private CheckoutFactoryInterface|MockObject $checkoutFactory;

    private GroupedCheckoutLineItemsProvider $provider;

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

    private function getCheckoutLineItem(
        int $id,
        ?string $sku = null,
        ?string $unitCode = null,
        string $checksum = ''
    ): CheckoutLineItem {
        $lineItem = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem, $id);
        if (null !== $sku) {
            $lineItem->setProductSku($sku);
        }
        if (null !== $unitCode) {
            $lineItem->setProductUnitCode($unitCode);
        }

        $lineItem->setChecksum($checksum);

        return $lineItem;
    }

    public function testGetGroupedLineItems(): void
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem(1);
        $checkoutLineItem2 = $this->getCheckoutLineItem(2);
        $checkoutLineItem3 = $this->getCheckoutLineItem(3);
        $checkoutLineItem4 = $this->getCheckoutLineItem(4);

        $checkoutSource = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem4,
        ]);

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkoutSource)
            ->willReturn(new ArrayCollection([$checkoutLineItem1, $checkoutLineItem2, $checkoutLineItem3]));

        $checkout = new Checkout();
        $checkout->setLineItems(
            new ArrayCollection([
                $checkoutLineItem1,
                $checkoutLineItem2,
                $checkoutLineItem3,
            ])
        );

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $groupedLineItems = [
            'product.owner:1' => [$checkoutLineItem1, $checkoutLineItem3],
            'product.owner:2' => [$checkoutLineItem2],
        ];

        $this->groupingService->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($checkout)
            ->willReturn($groupedLineItems);

        $result = $this->provider->getGroupedLineItems($checkoutSource);
        self::assertEquals($groupedLineItems, $result);
    }

    public function testGetGroupedLineItemsIds(): void
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem(1, 'sku-1', 'item');
        $checkoutLineItem2 = $this->getCheckoutLineItem(2, 'sku-2', 'set');
        $checkoutLineItem3 = $this->getCheckoutLineItem(3, 'sku-3', 'item');
        $checkoutLineItem3_1 = $this->getCheckoutLineItem(3, 'sku-3', 'item', 'sample_checksum');
        $checkoutLineItem4 = $this->getCheckoutLineItem(4, 'sku-4', 'set');

        $checkoutSource = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem3_1,
            $checkoutLineItem4,
        ]);

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkoutSource)
            ->willReturn(
                new ArrayCollection([$checkoutLineItem1, $checkoutLineItem2, $checkoutLineItem3, $checkoutLineItem3_1])
            );

        $checkout = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem3_1,
        ]);

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->willReturn($checkout);

        $groupedLineItems = [
            'product.owner:1' => [$checkoutLineItem1, $checkoutLineItem3, $checkoutLineItem3_1],
            'product.owner:2' => [$checkoutLineItem2],
        ];

        $this->groupingService->expects(self::once())
            ->method('getGroupedLineItems')
            ->with($checkout)
            ->willReturn($groupedLineItems);

        $result = $this->provider->getGroupedLineItemsIds($checkoutSource);
        self::assertEquals(
            [
                'product.owner:1' => ['sku-1:item:', 'sku-3:item:', 'sku-3:item:sample_checksum'],
                'product.owner:2' => ['sku-2:set:'],
            ],
            $result
        );
    }

    public function testGetGroupedLineItemsByIds(): void
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem(1, 'sku-1', 'item');
        $checkoutLineItem2 = $this->getCheckoutLineItem(2, 'sku-2', 'set');
        $checkoutLineItem3 = $this->getCheckoutLineItem(3, 'sku-3', 'item');
        $checkoutLineItem3_1 = $this->getCheckoutLineItem(3, 'sku-3', 'item', 'sample_checksum');
        $checkoutLineItem4 = $this->getCheckoutLineItem(4, 'sku-4', 'set');

        $checkout = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem3_1,
            $checkoutLineItem4,
        ]);

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item:', 'sku-3:item:', 'sku-3:item:sample_checksum'],
            'product.owner:2' => ['sku-2:set:'],
        ];

        $result = $this->provider->getGroupedLineItemsByIds($checkout, $groupedLineItemsIds);
        self::assertEquals(
            [
                'product.owner:1' => [$checkoutLineItem1, $checkoutLineItem3, $checkoutLineItem3_1],
                'product.owner:2' => [$checkoutLineItem2],
            ],
            $result
        );
    }
}
