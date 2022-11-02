<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\DatagridLineItemsDataPricingListener as PricingLineItemDataListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
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
use Oro\Component\Testing\Unit\EntityTrait;

class DatagridLineItemsDataPromotionsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PricingLineItemDataListener */
    private $pricingLineItemDataListener;

    /** @var PromotionExecutor */
    private $promotionExecutor;

    /** @var UserCurrencyManager */
    private $currencyManager;

    /** @var DatagridLineItemsDataPromotionsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->pricingLineItemDataListener = $this->createMock(PricingLineItemDataListener::class);
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);

        $numberFormatter = $this->createMock(NumberFormatter::class);
        $numberFormatter
            ->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(static fn (float $value, string $currency) => $currency . $value);

        $this->listener = new DatagridLineItemsDataPromotionsListener(
            $this->pricingLineItemDataListener,
            $this->promotionExecutor,
            $this->currencyManager,
            $numberFormatter
        );
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $this->pricingLineItemDataListener
            ->expects($this->once())
            ->method('onLineItemData')
            ->with($event);

        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenPromotionExecutorNotSupports(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $this->pricingLineItemDataListener
            ->expects($this->once())
            ->method('onLineItemData')
            ->with($event);

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 11, 'shoppingList' => $shoppingList]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 22]);

        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$lineItem1, $lineItem2]);

        $this->promotionExecutor
            ->expects($this->once())
            ->method('supports')
            ->with($shoppingList)
            ->willReturn(false);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoDiscountLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $this->pricingLineItemDataListener
            ->expects($this->once())
            ->method('onLineItemData')
            ->with($event);

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 11, 'shoppingList' => $shoppingList]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 22]);

        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$lineItem1, $lineItem2]);

        $this->promotionExecutor
            ->expects($this->once())
            ->method('supports')
            ->with($shoppingList)
            ->willReturn(true);

        $discountContext = $this->createMock(DiscountContextInterface::class);
        $discountContext
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $this->promotionExecutor
            ->expects($this->once())
            ->method('execute')
            ->with($shoppingList)
            ->willReturn($discountContext);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNotSourceLineItemNotShoppingListLineItem(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $this->pricingLineItemDataListener
            ->expects($this->once())
            ->method('onLineItemData')
            ->with($event);

        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 1]);
        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 11, 'shoppingList' => $shoppingList]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 22]);

        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$lineItem1, $lineItem2]);

        $this->promotionExecutor
            ->expects($this->once())
            ->method('supports')
            ->with($shoppingList)
            ->willReturn(true);

        $discountContext = $this->createMock(DiscountContextInterface::class);
        $discountContext
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $this->promotionExecutor
            ->expects($this->once())
            ->method('execute')
            ->with($shoppingList)
            ->willReturn($discountContext);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

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

        $this->currencyManager
            ->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $shoppingList = new ShoppingListStub();
        $shoppingList->setId(12);
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);
        $shoppingList->addLineItem($lineItem3);

        $this->promotionExecutor
            ->expects($this->once())
            ->method('supports')
            ->with($shoppingList)
            ->willReturn(true);
        $this->promotionExecutor
            ->expects($this->once())
            ->method('execute')
            ->with($shoppingList)
            ->willReturn($discountContext);

        $event = new DatagridLineItemsDataEvent(
            [$lineItem1, $lineItem2, $lineItem3],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->pricingLineItemDataListener
            ->expects($this->once())
            ->method('onLineItemData')
            ->with($event);

        $event->addDataForLineItem($lineItem1->getId(), ['subtotal' => 'USD100', 'subtotalValue' => 100]);
        $event->addDataForLineItem($lineItem2->getId(), ['subtotal' => 'USD1000', 'subtotalValue' => 1000]);
        $event->addDataForLineItem($lineItem3->getId(), ['subtotal' => 'USD500', 'subtotalValue' => 500]);

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            [
                'discountValue' => $discountInformation1->getDiscountAmount(),
                'discount' => 'USD30',
                'subtotal' => 'USD70',
                'subtotalValue' => '70',
                'initialSubtotal' => 'USD100',
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
            ],
            $event->getDataForLineItem($lineItem2->getId())
        );

        $this->assertEquals(
            [
                'subtotal' => 'USD500',
                'subtotalValue' => 500,
            ],
            $event->getDataForLineItem($lineItem3->getId())
        );
    }

    private function getLineItem(int $id, string $sku, string $unitCode, float $quantity): LineItem
    {
        $product = new Product();
        $product->setSku($sku);

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        return $this->getEntity(
            LineItem::class,
            ['id' => $id, 'product' => $product, 'unit' => $productUnit, 'quantity' => $quantity]
        );
    }
}
