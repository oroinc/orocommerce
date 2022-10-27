<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DiscountsInformationDataProvider;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DTO\ObjectStorage;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\Testing\Unit\EntityTrait;

class DiscountsInformationDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var PromotionExecutor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $promotionExecutor;

    /**
     * @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $currencyManager;

    /**
     * @var DiscountsInformationDataProvider
     */
    private $provider;

    protected function setUp(): void
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

        $result = $this->provider->getDiscountLineItemDiscounts($entity);
        $this->assertInstanceOf(ObjectStorage::class, $result);
        $this->assertEquals(0, $result->count());
    }

    public function testGetDiscountLineItemDiscounts()
    {
        $entity = new \stdClass();
        /** @var DiscountInterface $discount */
        $discount = $this->createMock(DiscountInterface::class);

        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 42]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 50]);

        $discountInformation1 = new DiscountInformation($discount, 30);
        $discountContext = new DiscountContext();
        $discountContext->addLineItem(
            (new DiscountLineItem())
                ->addDiscountInformation($discountInformation1)
                ->setSourceLineItem($lineItem1)
        );
        $discountInformation2 = new DiscountInformation($discount, 80.3);
        $discountContext->addLineItem(
            (new DiscountLineItem())
                ->addDiscountInformation($discountInformation2)
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
        $this->assertInstanceOf(ObjectStorage::class, $result);
        $this->assertCount(2, $result);
        $this->assertResultHasData($result, $lineItem1, 30, 'USD', [$discountInformation1]);
        $this->assertResultHasData($result, $lineItem2, 80.3, 'USD', [$discountInformation2]);
    }

    /**
     * @param ObjectStorage $result
     * @param object $lineItem
     * @param float $value
     * @param string $currency
     * @param array $expectedDetails
     */
    private function assertResultHasData(
        ObjectStorage $result,
        $lineItem,
        $value,
        $currency,
        array $expectedDetails
    ) {
        $this->assertTrue($result->contains($lineItem));
        $info = $result->get($lineItem);
        $this->assertIsArray($info);
        $this->assertArrayHasKey('total', $info);
        $this->assertInstanceOf(Price::class, $info['total']);
        $this->assertEquals($currency, $info['total']->getCurrency());
        $this->assertEquals($value, $info['total']->getValue());
        $this->assertArrayHasKey('details', $info);
        $this->assertSame($expectedDetails, $info['details']);
    }
}
