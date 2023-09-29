<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Calculable\ParameterBag\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag\Factory\ParameterBagCalculableQuoteFactory;
use Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag\ParameterBagCalculableQuote;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use PHPUnit\Framework\TestCase;

class ParameterBagCalculableQuoteFactoryTest extends TestCase
{
    public function testCreateCalculableQuote(): void
    {
        $lineItems = new ArrayCollection([$this->createMock(ShippingLineItem::class)]);

        $expectedCalculableQuote = new ParameterBagCalculableQuote(
            [
                ParameterBagCalculableQuote::FIELD_LINE_ITEMS => $lineItems,
            ]
        );

        $parameterBagCalculableQuoteFactory = new ParameterBagCalculableQuoteFactory();

        self::assertEquals(
            $expectedCalculableQuote,
            $parameterBagCalculableQuoteFactory->createCalculableQuote($lineItems)
        );
    }
}
