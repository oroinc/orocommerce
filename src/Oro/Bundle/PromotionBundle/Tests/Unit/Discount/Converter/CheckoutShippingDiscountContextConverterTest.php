<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Discount\Converter\CheckoutShippingDiscountContextConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutShippingDiscountContextConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CheckoutShippingDiscountContextConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new CheckoutShippingDiscountContextConverter();
    }

    public function testConvert(): void
    {
        $sourceEntity = $this->getCheckout(new ShoppingList());

        $discountContext = new DiscountContext();
        $discountContext->setShippingCost(15.0);
        $discountContext->setSubtotal(15.0);

        $this->assertEquals($discountContext, $this->converter->convert($sourceEntity));
    }

    public function testConvertUnsupportedException(): void
    {
        $entity = new \stdClass();
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Source entity "stdClass" is not supported.');

        $this->converter->convert($entity);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(object $entity, bool $isSupported): void
    {
        $this->assertEquals($isSupported, $this->converter->supports($entity));
    }

    public function supportsDataProvider(): array
    {
        return [
            'supported entity' => [
                'entity' => $this->getCheckout(new ShoppingList()),
                'isSupported' => true
            ],
            'support all source entities except QuoteDemand' => [
                'entity' => $this->getCheckout(new \stdClass()),
                'isSupported' => true
            ],
            'not support QuoteDemand source' => [
                'entity' => $this->getCheckout(new QuoteDemand()),
                'isSupported' => false
            ],
            'not supported entity' => [
                'entity' => new \stdClass(),
                'isSupported' => false
            ],
            'supported without source entity' => [
                'entity' => $this->getCheckout(null),
                'isSupported' => true
            ],
        ];
    }

    private function getCheckout(?object $sourceEntity): Checkout
    {
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->once())
            ->method('getEntity')
            ->willReturn($sourceEntity);
        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        $shippingPrice = Price::create(15, 'USD');
        $checkout->setShippingCost($shippingPrice);

        return $checkout;
    }
}
