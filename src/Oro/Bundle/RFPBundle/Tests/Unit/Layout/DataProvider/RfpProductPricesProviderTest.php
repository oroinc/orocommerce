<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Layout\DataProvider\RfpProductPricesProvider;

class RfpProductPricesProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPrices()
    {
        $frontendProductPricesProvider = $this->createMock(FrontendProductPricesProvider::class);

        $rfpProductPricesProvider = new RfpProductPricesProvider($frontendProductPricesProvider);

        $rfpRequest = $this->createMock(RFPRequest::class);

        $product = new Product();
        $requestProductsObject = new ArrayCollection([
            new RequestProduct(),
            (new RequestProduct())->setProduct($product)
        ]);

        $rfpRequest->expects($this->any())
            ->method('getRequestProducts')
            ->willReturn($requestProductsObject);

        $frontendProductPricesProvider->expects($this->atLeastOnce())
            ->method('getByProducts')
            ->with([$product])
            ->willReturn(['foo', 'bar']);

        $result = $rfpProductPricesProvider->getPrices($rfpRequest);

        $this->assertEquals(['foo', 'bar'], $result);
    }
}
