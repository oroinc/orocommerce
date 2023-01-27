<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitEntitiesProviderInterface;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Stub\CheckoutStub;
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
use PHPUnit\Framework\MockObject\MockObject;

class DatagridLineItemsDataPromotionsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private PricingLineItemDataListener|MockObject $pricingLineItemDataListener;
    private PromotionExecutor|MockObject $promotionExecutor;
    private UserCurrencyManager|MockObject $currencyManager;
    private SplitEntitiesProviderInterface|MockObject $splitEntitiesProvider;

    /** @var DatagridLineItemsDataPromotionsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->pricingLineItemDataListener = $this->createMock(PricingLineItemDataListener::class);
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->splitEntitiesProvider = $this->createMock(SplitEntitiesProviderInterface::class);

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

        $this->listener->setSplitEntitiesProvider($this->splitEntitiesProvider);
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

        $this->splitEntitiesProvider->expects($this->once())
            ->method('getSplitEntities')
            ->willReturn([]);

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

        $this->splitEntitiesProvider->expects($this->once())
            ->method('getSplitEntities')
            ->willReturn([]);

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

        $this->currencyManager
            ->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $shoppingList = new ShoppingListStub();
        $shoppingList->setId(12);
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);
        $shoppingList->addLineItem($lineItem3);

        $this->splitEntitiesProvider->expects($this->once())
            ->method('getSplitEntities')
            ->willReturn([]);

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

        $this->currencyManager
            ->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

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

        $this->promotionExecutor
            ->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $this->promotionExecutor
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturnMap([
                [$splitCheckout1, $discountContext],
                [$splitCheckout2, $discountContext2]
            ]);

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
                'discount' => 'USD10',
                'subtotal' => 'USD90',
                'subtotalValue' => '90',
                'initialSubtotal' => 'USD100',
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

    private function getCheckoutLineItem(int $id, string $sku, string $unitCode, float $quantity): CheckoutLineItem
    {
        $product = new Product();
        $product->setSku($sku);

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        return $this->getEntity(
            CheckoutLineItem::class,
            ['id' => $id, 'product' => $product, 'productUnit' => $productUnit, 'quantity' => $quantity]
        );
    }
}
