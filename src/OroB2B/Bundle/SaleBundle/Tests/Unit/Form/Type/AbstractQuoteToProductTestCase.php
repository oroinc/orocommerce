<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Model\QuoteProductOfferMatcher;
use OroB2B\Bundle\SaleBundle\Validator\Constraints;

abstract class AbstractQuoteToProductTestCase extends FormIntegrationTestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QuoteProductOfferMatcher
     */
    protected function getMatcher()
    {
        $matcher = $this->getMockBuilder('OroB2B\Bundle\SaleBundle\Model\QuoteProductOfferMatcher')
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
     * @return \PHPUnit_Framework_MockObject_MockObject|RoundingService
     */
    protected function getRoundingService()
    {
        $roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();
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
