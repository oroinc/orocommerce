<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\LineItemDataBuildListener as PricingLineItemDataListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\EventListener\LineItemDataBuildListener;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Component\Testing\Unit\EntityTrait;

class LineItemDataBuildListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PricingLineItemDataListener */
    private $pricingLineItemDataListener;

    /** @var PromotionExecutor */
    private $promotionExecutor;

    /** @var UserCurrencyManager */
    private $currencyManager;

    /** @var LineItemDataBuildListener */
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

        $this->listener = new LineItemDataBuildListener(
            $this->pricingLineItemDataListener,
            $this->promotionExecutor,
            $this->currencyManager,
            $numberFormatter
        );
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);

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
            ->method('setDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenPromotionExecutorNotSupports(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);

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
            ->method('setDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoDiscountLineItems(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);

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
            ->method('setDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNotSourceLineItemNotShoppingListLineItem(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);

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
        $discountLineItem1 = (new DiscountLineItem())->setSourceLineItem(new \stdClass());
        $discountContext
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$discountLineItem1]);

        $this->promotionExecutor
            ->expects($this->once())
            ->method('execute')
            ->with($shoppingList)
            ->willReturn($discountContext);

        $event
            ->expects($this->never())
            ->method('setDataForLineItem');

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

        /** @var LineItem $lineItem1 */
        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 42]);
        /** @var LineItem $lineItem2 */
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 50]);
        /** @var LineItem $lineItem3 */
        $lineItem3 = $this->getEntity(LineItem::class, ['id' => 60]);

        $discountLineItem1 = new DiscountLineItem();
        $discountLineItem1
            ->addDiscountInformation($discountInformation1)
            ->setSourceLineItem($lineItem1);

        $discountLineItem2 = new DiscountLineItem();
        $discountLineItem2
            ->addDiscountInformation($discountInformation2)
            ->setSourceLineItem($lineItem2);

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

        $event = new LineItemDataBuildEvent([$lineItem1, $lineItem2, $lineItem3], []);

        $this->pricingLineItemDataListener
            ->expects($this->once())
            ->method('onLineItemData')
            ->with($event);

        $event->setDataForLineItem($lineItem1->getId(), ['subtotal' => 'USD100', 'subtotalValue' => 100]);
        $event->setDataForLineItem($lineItem2->getId(), ['subtotal' => 'USD1000', 'subtotalValue' => 1000]);
        $event->setDataForLineItem($lineItem3->getId(), ['subtotal' => 'USD500', 'subtotalValue' => 500]);

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
}
