<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Layout\DataProvider\TierPricesProvider;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;

class TierPricesProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPrices()
    {
        $repository = $this->getMockBuilder(ProductPriceRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $priceListRequestHandler = $this->getMockBuilder(PriceListRequestHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $priceListRequestHandler->method('getPriceList')
            ->willReturn(1);

        $tierPriceProvider = new TierPricesProvider($repository, $priceListRequestHandler);

        $rfpRequest = $this->getMock(RFPRequest::class);

        $requestsProducts = [];
        $pricesByProduct = [];

        for ($i = 0; $i < 10; $i++) {
            $requestProduct = $this->getMock(RequestProduct::class);

            $product = $this->getMock(Product::class);

            $requestProduct->expects($this->atLeastOnce())
                ->method('getProduct')
                ->willReturn($product);

            $product->expects($this->atLeastOnce())
                ->method('getId')
                ->willReturn($i+1);

            $productPrice = $this->getMock(ProductPrice::class);

            $productPrice->method('getProduct')
                ->willReturn($product);

            $price = $this->getMock(Price::class);

            $productPrice->expects($this->atLeastOnce())
                ->method('getPrice')
                ->willReturn($price);

            $price->expects($this->atLeastOnce())
                ->method('getCurrency')
                ->willReturn('USD');

            $price->expects($this->atLeastOnce())
                ->method('getValue')
                ->willReturn($i * 5);

            $unit = $this->getMock(ProductUnit::class);

            $unit->expects($this->atLeastOnce())
                ->method('getCode')
                ->willReturn('item');

            $productPrice->method('getUnit')
                ->willReturn($unit);

            $pricesByProduct[] = $productPrice;
            $requestsProducts[] = $requestProduct;
        }

        $repository->expects($this->atLeastOnce())
            ->method('findByPriceListIdAndProductIds')
            ->willReturn($pricesByProduct);

        $rfpRequest->method('getRequestProducts')
            ->willReturn($requestsProducts);

        $result = $tierPriceProvider->getPrices($rfpRequest);

        foreach ($result as $id => $prices) {
            $this->assertGreaterThan(0, $id);

            foreach ($prices as $price) {
                $this->assertArrayHasKey('currency', $price);
                $this->assertArrayHasKey('price', $price);
                $this->assertArrayHasKey('quantity', $price);
                $this->assertArrayHasKey('unit', $price);
            }
        }
    }
}
