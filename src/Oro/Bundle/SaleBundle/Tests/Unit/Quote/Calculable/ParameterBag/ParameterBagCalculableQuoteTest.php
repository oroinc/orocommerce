<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Calculable\ParameterBag;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag\ParameterBagCalculableQuote;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use PHPUnit\Framework\TestCase;

class ParameterBagCalculableQuoteTest extends TestCase
{
    public function testGetters(): void
    {
        $lineItems = new ArrayCollection([$this->createMock(ShippingLineItemInterface::class)]);

        $calculableQuote = new ParameterBagCalculableQuote([
            ParameterBagCalculableQuote::FIELD_LINE_ITEMS => $lineItems
        ]);

        self::assertEquals($lineItems, $calculableQuote->getLineItems());
    }
}
