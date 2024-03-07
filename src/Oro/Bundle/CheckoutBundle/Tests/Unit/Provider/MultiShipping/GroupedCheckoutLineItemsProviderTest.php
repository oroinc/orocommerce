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

        $checkout = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem4
        ]);

        $filteredLineItems = new ArrayCollection([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3
        ]);
        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($filteredLineItems);

        $checkoutToGetData = new Checkout();
        $checkoutToGetData->setLineItems(new ArrayCollection($filteredLineItems->toArray()));

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->with(self::identicalTo($checkout), self::identicalTo($filteredLineItems))
            ->willReturn($checkoutToGetData);

        $groupedLineItems = [
            'product.owner:1' => [$checkoutLineItem1, $checkoutLineItem3],
            'product.owner:2' => [$checkoutLineItem2]
        ];

        $this->groupingService->expects(self::once())
            ->method('getGroupedLineItems')
            ->with(self::identicalTo($checkoutToGetData))
            ->willReturn($groupedLineItems);

        self::assertEquals($groupedLineItems, $this->provider->getGroupedLineItems($checkout));
    }

    public function testGetGroupedLineItemsIds(): void
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem(1, 'sku-1', 'item');
        $checkoutLineItem2 = $this->getCheckoutLineItem(2, 'sku-2', 'set');
        $checkoutLineItem3 = $this->getCheckoutLineItem(3, 'sku-3', 'item');
        $checkoutLineItem31 = $this->getCheckoutLineItem(3, 'sku-3', 'item', 'sample_checksum');
        $checkoutLineItem4 = $this->getCheckoutLineItem(4, 'sku-4', 'set');

        $checkout = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem31,
            $checkoutLineItem4
        ]);

        $filteredLineItems = new ArrayCollection([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem31
        ]);
        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($filteredLineItems);

        $checkoutToGetData = new Checkout();
        $checkoutToGetData->setLineItems(new ArrayCollection($filteredLineItems->toArray()));

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->with(self::identicalTo($checkout), self::identicalTo($filteredLineItems))
            ->willReturn($checkoutToGetData);

        $this->groupingService->expects(self::once())
            ->method('getGroupedLineItems')
            ->with(self::identicalTo($checkoutToGetData))
            ->willReturn([
                'product.owner:1' => [$checkoutLineItem1, $checkoutLineItem3, $checkoutLineItem31],
                'product.owner:2' => [$checkoutLineItem2]
            ]);

        self::assertEquals(
            [
                'product.owner:1' => ['sku-1:item:', 'sku-3:item:', 'sku-3:item:sample_checksum'],
                'product.owner:2' => ['sku-2:set:']
            ],
            $this->provider->getGroupedLineItemsIds($checkout)
        );
    }

    public function testGetGroupedLineItemsByIds(): void
    {
        $checkoutLineItem1 = $this->getCheckoutLineItem(1, 'sku-1', 'item');
        $checkoutLineItem2 = $this->getCheckoutLineItem(2, 'sku-2', 'set');
        $checkoutLineItem3 = $this->getCheckoutLineItem(3, 'sku-3', 'item');
        $checkoutLineItem31 = $this->getCheckoutLineItem(3, 'sku-3', 'item', 'sample_checksum');
        $checkoutLineItem4 = $this->getCheckoutLineItem(4, 'sku-4', 'set');

        $checkout = $this->getCheckout([
            $checkoutLineItem1,
            $checkoutLineItem2,
            $checkoutLineItem3,
            $checkoutLineItem31,
            $checkoutLineItem4
        ]);

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item:', 'sku-3:item:', 'sku-3:item:sample_checksum'],
            'product.owner:2' => ['sku-2:set:']
        ];

        self::assertEquals(
            [
                'product.owner:1' => [$checkoutLineItem1, $checkoutLineItem3, $checkoutLineItem31],
                'product.owner:2' => [$checkoutLineItem2]
            ],
            $this->provider->getGroupedLineItemsByIds($checkout, $groupedLineItemsIds)
        );
    }
}
