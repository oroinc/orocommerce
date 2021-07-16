<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CheckoutSubtotalTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /**
     * @dataProvider priceListDataProvider
     */
    public function testAccessorsForPriceLists(BasePriceList $priceList)
    {
        $priceList->setName('test price list');
        $checkout = new Checkout();
        $entity = new CheckoutSubtotal($checkout, 'USD');
        $this->assertPropertyAccessors($entity, [
            ['id', 1],
            ['valid', true]
        ]);

        $this->assertSame($checkout, $entity->getCheckout());
        $this->assertSame('USD', $entity->getCurrency());

        $subtotal = (new Subtotal())->setCurrency('USD')->setAmount(123);
        $subtotal->setPriceList($priceList);
        $entity->setSubtotal($subtotal);
        $this->assertSame('USD', $entity->getSubtotal()->getCurrency());
        $this->assertSame(123.0, $entity->getSubtotal()->getAmount());
        $this->assertSame('test price list', $entity->getSubtotal()->getPriceList()->getName());
    }

    public function priceListDataProvider(): array
    {
        return [
            'flat pl' => [new PriceList()],
            'cpl' => [new CombinedPriceList()]
        ];
    }

    public function testExceptionWhenDifferentSubtotalValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid currency for Checkout Subtotal');

        $checkout = new Checkout();
        $entity = new CheckoutSubtotal($checkout, 'USD');
        $subtotal = (new Subtotal())->setCurrency('EUR')->setAmount(123);
        $entity->setSubtotal($subtotal);
    }
}
