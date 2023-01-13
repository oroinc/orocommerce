<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Calculable\ParameterBag;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag\ParameterBagCalculableQuote;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

class ParameterBagCalculableQuoteTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $lineItems = new ArrayCollection([$this->createMock(ShippingLineItemInterface::class)]);

        $calculableQuote = new ParameterBagCalculableQuote([
            ParameterBagCalculableQuote::FIELD_LINE_ITEMS => $lineItems
        ]);

        $this->assertEquals($lineItems, $calculableQuote->getLineItems());
    }
}
