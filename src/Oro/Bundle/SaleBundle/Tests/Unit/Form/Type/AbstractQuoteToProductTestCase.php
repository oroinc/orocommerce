<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Model\QuoteProductOfferMatcher;
use Oro\Bundle\SaleBundle\Validator\Constraints;

abstract class AbstractQuoteToProductTestCase extends FormIntegrationTestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QuoteProductOfferMatcher
     */
    protected function getMatcher()
    {
        $matcher = $this->getMockBuilder('Oro\Bundle\SaleBundle\Model\QuoteProductOfferMatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $matcher->expects($this->any())
            ->method('match')
            ->willReturnCallback(
                function (QuoteProduct $quoteProduct, $unitCode, $quantity) {
                    // simple emulation of original match algorithm
                    return $quoteProduct->getQuoteProductOffers()->filter(
                        function (QuoteProductOffer $offer) use ($quantity) {
                            return $offer->getQuantity() == $quantity;
                        }
                    )->first();
                }
            );

        return $matcher;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RoundingServiceInterface
     */
    protected function getRoundingService()
    {
        $roundingService = $this->getMock('Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface');
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(
                function ($value, $precision) {
                    return round($value, $precision, PHP_ROUND_HALF_UP);
                }
            );

        return $roundingService;
    }
}
