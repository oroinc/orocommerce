<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Layout\DataProvider\RfpProductPricesProvider;

class RfpProductPricesProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPrices()
    {
        $frontendProductPricesProvider = $this->createMock(FrontendProductPricesProvider::class);

        $rfpProductPricesProvider = new RfpProductPricesProvider($frontendProductPricesProvider);

        $rfpRequest = $this->createMock(RFPRequest::class);

        $requestProductsObject = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['map', 'toArray'])
            ->getMock();
        $requestProductsObject->expects($this->atLeastOnce())
            ->method('map')
            ->willReturn($requestProductsObject);
        $requestProductsObject->expects($this->atLeastOnce())
            ->method('toArray')
            ->willReturn([]);

        $rfpRequest->expects($this->any())
            ->method('getRequestProducts')
            ->willReturn($requestProductsObject);

        $frontendProductPricesProvider->expects($this->atLeastOnce())
            ->method('getByProducts')
            ->willReturn(['foo', 'bar']);

        $result = $rfpProductPricesProvider->getPrices($rfpRequest);

        $this->assertEquals(['foo', 'bar'], $result);
    }
}
