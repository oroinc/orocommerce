<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\EventListener\LineItemDataListener;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataEvent;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Component\Testing\Unit\EntityTrait;

class LineItemDataListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PromotionExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionExecutor;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var LineItemDataListener */
    private $listener;

    protected function setUp(): void
    {
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);

        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(
                static function (float $value, string $currency) {
                    return $currency . $value;
                }
            );

        $this->listener = new LineItemDataListener(
            $this->promotionExecutor,
            $this->currencyManager,
            $this->numberFormatter
        );
    }

    public function testOnLineItemData(): void
    {
        $discount = $this->createMock(DiscountInterface::class);

        $discountInformation1 = new DiscountInformation($discount, 30);
        $discountInformation2 = new DiscountInformation($discount, 80.3);

        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 42]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 50]);
        $lineItem3 = $this->getEntity(LineItem::class, ['id' => 60]);

        $discountLineItem1 = new DiscountLineItem();
        $discountLineItem1->addDiscountInformation($discountInformation1)
            ->setSourceLineItem($lineItem1);

        $discountLineItem2 = new DiscountLineItem();
        $discountLineItem2->addDiscountInformation($discountInformation2)
            ->setSourceLineItem($lineItem2);

        $discountContext = new DiscountContext();
        $discountContext->addLineItem($discountLineItem1);
        $discountContext->addLineItem($discountLineItem2);

        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $shoppingList = new ShoppingListStub();
        $shoppingList->setId(12);
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);
        $shoppingList->addLineItem($lineItem3);

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($shoppingList)
            ->willReturn(true);
        $this->promotionExecutor->expects($this->once())
            ->method('execute')
            ->with($shoppingList)
            ->willReturn($discountContext);

        $event = new LineItemDataEvent([$lineItem1, $lineItem2, $lineItem3]);

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            ['discountValue' => $discountInformation1->getDiscountAmount(), 'discount' => 'USD30'],
            $event->getDataForLineItem($lineItem1->getId())
        );
        $this->assertEquals(
            ['discountValue' => $discountInformation2->getDiscountAmount(), 'discount' => 'USD80.3'],
            $event->getDataForLineItem($lineItem2->getId())
        );
        $this->assertEquals([], $event->getDataForLineItem($lineItem3->getId()));
    }
}
