<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use PHPUnit\Framework\TestCase;

class SubtotalTest extends TestCase
{
    public function testProperties(): void
    {
        $subtotal = new Subtotal();

        self::assertEquals(
            LineItemSubtotalProvider::TYPE,
            $subtotal->setType(LineItemSubtotalProvider::TYPE)->getType()
        );
        self::assertEquals('Subtotal', $subtotal->setLabel('Subtotal')->getLabel());
        self::assertEquals('USD', $subtotal->setCurrency('USD')->getCurrency());
        self::assertEquals(999.99, $subtotal->setAmount(999.99)->getAmount());
        self::assertTrue($subtotal->setVisible(true)->isVisible());
        self::assertFalse($subtotal->setRemovable(false)->isRemovable());
        self::assertEquals(['some value'], $subtotal->setData(['some value'])->getData());
        self::assertEquals(987, $subtotal->setSortOrder(987)->getSortOrder());
        self::assertEquals(
            'test',
            $subtotal->setPriceList((new CombinedPriceList())->setName('test'))
                ->getPriceList()->getName()
        );
    }

    public function testToArray(): void
    {
        $subtotal = new Subtotal();

        self::assertEquals(
            [
                'type' => $subtotal->getType(),
                'label' => $subtotal->getLabel(),
                'amount' => $subtotal->getAmount(),
                'currency' => $subtotal->getCurrency(),
                'visible' => $subtotal->isVisible(),
                'data' => $subtotal->getData(),
                'signedAmount' => $subtotal->getSignedAmount(),
            ],
            $subtotal->toArray()
        );
    }

    public function testGetSignedAmount(): void
    {
        $subtotal = new Subtotal();
        self::assertEquals(0.0, $subtotal->getAmount());
        $subtotal->setAmount(10);

        self::assertEquals(10, $subtotal->getSignedAmount());

        $subtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);

        self::assertEquals(-10, $subtotal->getSignedAmount());
    }

    public function testGetTotalPrice(): void
    {
        $subtotal = new Subtotal();
        $subtotal->setCurrency('USD')
            ->setAmount(10);

        $expected = (new Price())->setValue(10)->setCurrency('USD');
        self::assertEquals($expected, $subtotal->getTotalPrice());
    }
}
