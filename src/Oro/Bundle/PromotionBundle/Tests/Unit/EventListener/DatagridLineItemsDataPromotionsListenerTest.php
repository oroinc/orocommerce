<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitEntitiesProviderInterface;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Stub\CheckoutStub;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\EventListener\DatagridLineItemsDataPromotionsListener;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Component\Testing\ReflectionUtil;

class DatagridLineItemsDataPromotionsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PromotionExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionExecutor;

    /** @var SplitEntitiesProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $splitEntitiesProvider;

    /** @var DatagridLineItemsDataPromotionsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);
        $this->splitEntitiesProvider = $this->createMock(SplitEntitiesProviderInterface::class);

        $numberFormatter = $this->createMock(NumberFormatter::class);
        $numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(static fn (float $value, string $currency) => $currency . $value);

        $this->listener = new DatagridLineItemsDataPromotionsListener(
            $this->promotionExecutor,
            $numberFormatter,
            $this->splitEntitiesProvider
        );
    }

    private function getLineItem(
        int $id,
        ?string $sku = null,
        ?string $unitCode = null,
        ?float $quantity = null
    ): LineItem {
        $lineItem = new LineItem();
        ReflectionUtil::setId($lineItem, $id);
        if (null !== $sku) {
            $product = new Product();
            $product->setSku($sku);
            $lineItem->setProduct($product);
        }
        if (null !== $unitCode) {
            $productUnit = new ProductUnit();
            $productUnit->setCode($unitCode);
            $lineItem->setUnit($productUnit);
        }
        if (null !== $quantity) {
            $lineItem->setQuantity($quantity);
        }

        return $lineItem;
    }

    private function getCheckoutLineItem(int $id, string $sku, string $unitCode, float $quantity): CheckoutLineItem
    {
        $product = new Product();
        $product->setSku($sku);

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        $lineItem = new CheckoutLineItem();
        ReflectionUtil::setId($lineItem, $id);
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity($quantity);

        return $lineItem;
    }

    private function getShoppingList(int $id): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);

        return $shoppingList;
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);
        $event->expects($this->never())
            ->method('addDataForLineItem');
        $event->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenPromotionExecutorNotSupports(): void
    {
        $shoppingList = $this->getShoppingList(1);
        $lineItem1 = $this->getLineItem(11);
        $lineItem1->setShoppingList($shoppingList);
        $lineItem2 = $this->getLineItem(22);

        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$lineItem1, $lineItem2]);
        $event->expects($this->never())
            ->method('addDataForLineItem');
        $event->expects($this->never())
            ->method('addDataForLineItem');

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($shoppingList)
            ->willReturn(false);

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoDiscountLineItems(): void
    {
        $shoppingList = $this->getShoppingList(1);
        $lineItem1 = $this->getLineItem(11);
        $lineItem1->setShoppingList($shoppingList);
        $lineItem2 = $this->getLineItem(22);

        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$lineItem1, $lineItem2]);
        $event->expects($this->never())
            ->method('addDataForLineItem');
        $event->expects($this->never())
            ->method('addDataForLineItem');

        $this->splitEntitiesProvider->expects($this->once())
            ->method('getSplitEntities')
            ->willReturn([]);

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($shoppingList)
            ->willReturn(true);

        $discountContext = $this->createMock(DiscountContextInterface::class);
        $discountContext->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $this->promotionExecutor->expects($this->once())
            ->method('execute')
            ->with($shoppingList)
            ->willReturn($discountContext);

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNotSourceLineItemNotShoppingListLineItem(): void
    {
        $shoppingList = $this->getShoppingList(1);
        $lineItem1 = $this->getLineItem(11);
        $lineItem1->setShoppingList($shoppingList);
        $lineItem2 = $this->getLineItem(22);

        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$lineItem1, $lineItem2]);
        $event->expects($this->never())
            ->method('addDataForLineItem');
        $event->expects($this->never())
            ->method('addDataForLineItem');

        $this->splitEntitiesProvider->expects($this->once())
            ->method('getSplitEntities')
            ->willReturn([]);

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($shoppingList)
            ->willReturn(true);

        $discountContext = $this->createMock(DiscountContextInterface::class);
        $discountContext->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $this->promotionExecutor->expects($this->once())
            ->method('execute')
            ->with($shoppingList)
            ->willReturn($discountContext);

        $this->listener->onLineItemData($event);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnLineItemData(): void
    {
        $discount = $this->createMock(DiscountInterface::class);

        $discountInformation1 = new DiscountInformation($discount, 30);
        $discountInformation2 = new DiscountInformation($discount, 80.3);

        $lineItem1 = $this->getLineItem(42, 'sku1', 'item', 10);
        $lineItem2 = $this->getLineItem(50, 'sku2', 'item', 20);
        $lineItem3 = $this->getLineItem(60, 'sku3', 'item', 30);

        $discountLineItem1 = new DiscountLineItem();
        $discountLineItem1->addDiscountInformation($discountInformation1)
            ->setSourceLineItem($lineItem1)
            ->setProduct($lineItem1->getProduct())
            ->setProductUnit($lineItem1->getProductUnit())
            ->setQuantity($lineItem1->getQuantity());

        $discountLineItem2 = new DiscountLineItem();
        $discountLineItem2->addDiscountInformation($discountInformation2)
            ->setSourceLineItem($lineItem2)
            ->setProduct($lineItem2->getProduct())
            ->setProductUnit($lineItem2->getProductUnit())
            ->setQuantity($lineItem2->getQuantity());

        $discountContext = new DiscountContext();
        $discountContext->addLineItem($discountLineItem1);
        $discountContext->addLineItem($discountLineItem2);

        $shoppingList = new ShoppingListStub();
        $shoppingList->setId(12);
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);
        $shoppingList->addLineItem($lineItem3);

        $this->splitEntitiesProvider->expects($this->once())
            ->method('getSplitEntities')
            ->willReturn([]);

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($shoppingList)
            ->willReturn(true);
        $this->promotionExecutor->expects($this->once())
            ->method('execute')
            ->with($shoppingList)
            ->willReturn($discountContext);

        $event = new DatagridLineItemsDataEvent(
            [
                $lineItem1->getEntityIdentifier() => $lineItem1,
                $lineItem2->getEntityIdentifier() => $lineItem2,
                $lineItem3->getEntityIdentifier() => $lineItem3
            ],
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $event->addDataForLineItem(
            $lineItem1->getId(),
            ['currency' => 'USD', 'subtotal' => 'USD100', 'subtotalValue' => 100]
        );
        $event->addDataForLineItem(
            $lineItem2->getId(),
            ['currency' => 'USD', 'subtotal' => 'USD1000', 'subtotalValue' => 1000]
        );
        $event->addDataForLineItem(
            $lineItem3->getId(),
            ['currency' => 'USD', 'subtotal' => 'USD500', 'subtotalValue' => 500]
        );

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            [
                'discountValue' => $discountInformation1->getDiscountAmount(),
                'discount' => 'USD30',
                'subtotal' => 'USD70',
                'subtotalValue' => 70.0,
                'initialSubtotal' => 'USD100',
                'currency' => 'USD',
            ],
            $event->getDataForLineItem($lineItem1->getId())
        );

        $this->assertEquals(
            [
                'discountValue' => $discountInformation2->getDiscountAmount(),
                'discount' => 'USD80.3',
                'subtotal' => 'USD919.7',
                'subtotalValue' => 919.7,
                'initialSubtotal' => 'USD1000',
                'currency' => 'USD',
            ],
            $event->getDataForLineItem($lineItem2->getId())
        );

        $this->assertEquals(
            [
                'subtotal' => 'USD500',
                'subtotalValue' => 500,
                'currency' => 'USD',
            ],
            $event->getDataForLineItem($lineItem3->getId())
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnLineItemDataWithSubEntities(): void
    {
        $discount = $this->createMock(DiscountInterface::class);

        $discountInformation1 = new DiscountInformation($discount, 10.00);
        $discountInformation2 = new DiscountInformation($discount, 15.00);

        $lineItem1 = $this->getCheckoutLineItem(1, 'sku1', 'item', 10);
        $lineItem2 = $this->getCheckoutLineItem(2, 'sku2', 'item', 20);
        $lineItem3 = $this->getCheckoutLineItem(3, 'sku3', 'item', 30);

        $discountLineItem1 = new DiscountLineItem();
        $discountLineItem1->addDiscountInformation($discountInformation1)
            ->setSourceLineItem($lineItem1)
            ->setProduct($lineItem1->getProduct())
            ->setProductUnit($lineItem1->getProductUnit())
            ->setQuantity($lineItem1->getQuantity());

        $discountLineItem2 = new DiscountLineItem();
        $discountLineItem2->addDiscountInformation($discountInformation2)
            ->setSourceLineItem($lineItem2)
            ->setProduct($lineItem2->getProduct())
            ->setProductUnit($lineItem2->getProductUnit())
            ->setQuantity($lineItem2->getQuantity());

        $discountContext = new DiscountContext();
        $discountContext->addLineItem($discountLineItem1);
        $discountContext->addLineItem($discountLineItem2);

        $discountContext2 = new DiscountContext();

        $checkout = new CheckoutStub();
        $checkout->setId(5);
        $checkout->addLineItem($lineItem1);
        $checkout->addLineItem($lineItem2);
        $checkout->addLineItem($lineItem3);

        $splitCheckout1 = (new Checkout())->setLineItems(new ArrayCollection([$lineItem1, $lineItem2]));
        $splitCheckout2 = (new Checkout())->setLineItems(new ArrayCollection([$lineItem3]));

        $this->splitEntitiesProvider->expects($this->once())
            ->method('getSplitEntities')
            ->willReturn([$splitCheckout1, $splitCheckout2]);

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $this->promotionExecutor->expects($this->exactly(2))
            ->method('execute')
            ->willReturnMap([
                [$splitCheckout1, $discountContext],
                [$splitCheckout2, $discountContext2]
            ]);

        $event = new DatagridLineItemsDataEvent(
            [
                $lineItem1->getEntityIdentifier() => $lineItem1,
                $lineItem2->getEntityIdentifier() => $lineItem2,
                $lineItem3->getEntityIdentifier() => $lineItem3
            ],
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $event->addDataForLineItem(
            $lineItem1->getId(),
            ['currency' => 'USD', 'subtotal' => 'USD100', 'subtotalValue' => 100]
        );
        $event->addDataForLineItem(
            $lineItem2->getId(),
            ['currency' => 'USD', 'subtotal' => 'USD1000', 'subtotalValue' => 1000]
        );
        $event->addDataForLineItem(
            $lineItem3->getId(),
            ['currency' => 'USD', 'subtotal' => 'USD500', 'subtotalValue' => 500]
        );

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            [
                'discountValue' => $discountInformation1->getDiscountAmount(),
                'discount' => 'USD10',
                'subtotal' => 'USD90',
                'subtotalValue' => 90,
                'initialSubtotal' => 'USD100',
                'currency' => 'USD',
            ],
            $event->getDataForLineItem($lineItem1->getId())
        );

        $this->assertEquals(
            [
                'discountValue' => $discountInformation2->getDiscountAmount(),
                'discount' => 'USD15',
                'subtotal' => 'USD985',
                'subtotalValue' => 985.00,
                'initialSubtotal' => 'USD1000',
                'currency' => 'USD',
            ],
            $event->getDataForLineItem($lineItem2->getId())
        );

        $this->assertEquals(
            [
                'subtotal' => 'USD500',
                'subtotalValue' => 500,
                'currency' => 'USD',
            ],
            $event->getDataForLineItem($lineItem3->getId())
        );
    }
}
