<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupedLineItemsProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupedCheckoutLineItemsProviderTest extends TestCase
{
    use EntityTrait;

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

    public function testGetGroupedLineItems()
    {
        $checkoutLineItem1 = $this->getEntity(CheckoutLineItem::class, ['id' => 1]);
        $checkoutLineItem2 = $this->getEntity(CheckoutLineItem::class, ['id' => 2]);
        $checkoutLineItem3 = $this->getEntity(CheckoutLineItem::class, ['id' => 3]);
        $checkoutLineItem4 = $this->getEntity(CheckoutLineItem::class, ['id' => 4]);

        $checkoutSource = $this->getEntity(Checkout::class, [
            'lineItems' => new ArrayCollection([
                $checkoutLineItem1,
                $checkoutLineItem2,
                $checkoutLineItem3,
                $checkoutLineItem4
            ])
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

        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('product.owner:1', $result);
        $this->assertArrayHasKey('product.owner:2', $result);

        $this->assertCount(2, $result['product.owner:1']);
        $this->assertCount(1, $result['product.owner:2']);
    }

    public function testGetGroupedLineItemsIds()
    {
        $checkoutLineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-1',
            'productUnitCode' => 'item'
        ]);

        $checkoutLineItem2 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-2',
            'productUnitCode' => 'set'
        ]);

        $checkoutLineItem3 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-3',
            'productUnitCode' => 'item'
        ]);

        $checkoutLineItem4 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-4',
            'productUnitCode' => 'set'
        ]);

        $checkoutSource = $this->getEntity(Checkout::class, [
            'lineItems' => new ArrayCollection([
                $checkoutLineItem1,
                $checkoutLineItem2,
                $checkoutLineItem3,
                $checkoutLineItem4
            ])
        ]);

        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getCheckoutLineItems')
            ->with($checkoutSource)
            ->willReturn(new ArrayCollection([$checkoutLineItem1, $checkoutLineItem2, $checkoutLineItem3]));

        $checkout = $this->getEntity(Checkout::class, [
            'lineItems' => new ArrayCollection([
                $checkoutLineItem1,
                $checkoutLineItem2,
                $checkoutLineItem3
            ])
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

        $expected = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $result = $this->provider->getGroupedLineItemsIds($checkoutSource);

        $this->assertEquals($expected, $result);
    }

    public function testGetGroupedLineItemsByIds()
    {
        $checkoutLineItem1 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-1',
            'productUnitCode' => 'item'
        ]);

        $checkoutLineItem2 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-2',
            'productUnitCode' => 'set'
        ]);

        $checkoutLineItem3 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-3',
            'productUnitCode' => 'item'
        ]);

        $checkoutLineItem4 = $this->getEntity(CheckoutLineItem::class, [
            'productSku' => 'sku-4',
            'productUnitCode' => 'set'
        ]);

        $checkout = $this->getEntity(Checkout::class, [
            'lineItems' => new ArrayCollection([
                $checkoutLineItem1,
                $checkoutLineItem2,
                $checkoutLineItem3,
                $checkoutLineItem4
            ])
        ]);

        $groupedLineItemsIds = [
            'product.owner:1' => ['sku-1:item','sku-3:item'],
            'product.owner:2' => ['sku-2:set']
        ];

        $expectedResult = [
            'product.owner:1' => [
                0 => $checkoutLineItem1,
                2 => $checkoutLineItem3
            ],
            'product.owner:2' => [
                1 => $checkoutLineItem2
            ]
        ];

        $result = $this->provider->getGroupedLineItemsByIds($checkout, $groupedLineItemsIds);
        $this->assertEquals($expectedResult, $result);
    }
}
