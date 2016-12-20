<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Layout\DataProvider\RfpProductPricesProvider;
use Oro\Bundle\RFPBundle\Layout\DataProvider\TierPricesProvider;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;

class RfpProductPricesProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPrices()
    {
        $frontendProductPricesProvider = $this->getMockBuilder(FrontendProductPricesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rfpProductPricesProvider = new RfpProductPricesProvider($frontendProductPricesProvider);

        $rfpRequest = $this->getMock(RFPRequest::class);

        $requestProductsObject = $this->getMock('\StdClass', ['map', 'toArray']);
        $requestProductsObject->expects($this->atLeastOnce())
            ->method('map')
            ->willReturn($requestProductsObject);
        $requestProductsObject->expects($this->atLeastOnce())
            ->method('toArray')
            ->willReturn([]);

        $rfpRequest->method('getRequestProducts')
            ->willReturn($requestProductsObject);

        $frontendProductPricesProvider->expects($this->atLeastOnce())
            ->method('getByProducts')
            ->willReturn(['foo', 'bar']);

        $result = $rfpProductPricesProvider->getPrices($rfpRequest);

        $this->assertEquals(['foo', 'bar'], $result);
    }
}
