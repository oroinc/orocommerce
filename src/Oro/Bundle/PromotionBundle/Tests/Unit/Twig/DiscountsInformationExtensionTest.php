<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DiscountsInformationDataProvider;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\DTO\ObjectStorage;
use Oro\Bundle\PromotionBundle\Twig\DiscountsInformationExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DiscountsInformationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait, TwigExtensionTestCaseTrait;

    /** @var DiscountsInformationDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $dataProvider;

    /** @var DiscountsInformationExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->dataProvider = $this->createMock(DiscountsInformationDataProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_promotion.layout.discount_information_data_provider', $this->dataProvider)
            ->getContainer($this);

        $this->extension = new DiscountsInformationExtension($container);
    }

    public function testGetEmptyLineItemsDiscounts()
    {
        $lineItem = $this->getEntity(OrderLineItem::class, ['id' => 1]);
        $sourceEntity = $this->getEntity(
            Order::class,
            [
                'id' => 1,
                'lineItems' => [$lineItem]
            ]
        );

        $this->dataProvider->expects($this->once())
            ->method('getDiscountLineItemDiscounts')
            ->with($sourceEntity)
            ->willReturn(new ObjectStorage());

        $this->assertEquals(
            [1 => null],
            self::callTwigFunction($this->extension, 'line_items_discounts', [$sourceEntity])
        );
    }

    public function testGetLineItemsDiscounts()
    {
        $lineItem1Id = 1;
        $lineItem2Id = 2;
        $lineItem1 = $this->getEntity(OrderLineItem::class, ['id' => $lineItem1Id]);
        $lineItem2 = $this->getEntity(OrderLineItem::class, ['id' => $lineItem2Id]);
        $sourceEntity = $this->getEntity(
            Order::class,
            [
                'id' => 2,
                'lineItems' => [$lineItem1, $lineItem2]
            ]
        );

        $priceData = ['value' => 3, 'currency' => 'USD'];
        $price = $this->getEntity(Price::class, $priceData);
        $lineItemsDiscounts = new ObjectStorage();
        $lineItemsDiscounts->attach(
            $lineItem1,
            [
                'total' => $price
            ]
        );

        $this->dataProvider->expects($this->once())
            ->method('getDiscountLineItemDiscounts')
            ->with($sourceEntity)
            ->willReturn($lineItemsDiscounts);

        $expectedDiscounts = [
            $lineItem1Id => $priceData,
            $lineItem2Id => null
        ];
        $this->assertEquals(
            $expectedDiscounts,
            self::callTwigFunction($this->extension, 'line_items_discounts', [$sourceEntity])
        );
    }
}
