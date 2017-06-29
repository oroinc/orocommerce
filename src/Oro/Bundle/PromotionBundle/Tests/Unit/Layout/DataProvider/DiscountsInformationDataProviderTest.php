<?php

namespace Oro\Bundle\PromotionBundle\Test\Unit\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DiscountsInformationDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\Testing\Unit\EntityTrait;

class DiscountsInformationDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PromotionExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $promotionExecutor;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currencyManager;

    /**
     * @var DiscountsInformationDataProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);

        $this->provider = new DiscountsInformationDataProvider($this->promotionExecutor, $this->currencyManager);
    }

    public function testGetDiscountLineItemDiscountsUnsupported()
    {
        $entity = new \stdClass();

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($entity)
            ->willReturn(false);
        $this->promotionExecutor->expects($this->never())
            ->method('execute');

        $this->assertSame([], $this->provider->getDiscountLineItemDiscounts($entity));
    }

    public function testGetDiscountLineItemDiscounts()
    {
        $entity = new \stdClass();
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);

        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 42]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 50]);

        $discountContext = new DiscountContext();
        $discountContext->addLineItem(
            (new DiscountLineItem())
                ->addDiscountInformation(new DiscountInformation($discount, 30))
                ->setSourceLineItem($lineItem1)
        );
        $discountContext->addLineItem(
            (new DiscountLineItem())
                ->addDiscountInformation(new DiscountInformation($discount, 80.3))
                ->setSourceLineItem($lineItem2)
        );

        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($entity)
            ->willReturn(true);
        $this->promotionExecutor->expects($this->once())
            ->method('execute')
            ->with($entity)
            ->willReturn($discountContext);

        $result = $this->provider->getDiscountLineItemDiscounts($entity);
        $this->assertCount(2, $result);
        $this->assertResultHasData($result, 42, 30, 'USD');
        $this->assertResultHasData($result, 50, 80.3, 'USD');
    }

    /**
     * @param array|Price[] $result
     * @param int $id
     * @param float $value
     * @param string $currency
     */
    private function assertResultHasData(array $result, $id, $value, $currency)
    {
        $this->assertArrayHasKey($id, $result);
        $this->assertInstanceOf(Price::class, $result[$id]);
        $this->assertEquals($currency, $result[$id]->getCurrency());
        $this->assertEquals($value, $result[$id]->getValue());
    }
}
