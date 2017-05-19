<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Product;

use Doctrine\ORM\EntityRepository;

class PriceAttributePricesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var PriceAttributePricesProvider
     */
    protected $priceAttributePricesProvider;

    protected function setUp()
    {
        $this->helper = $this->createMock(DoctrineHelper::class);
        $this->priceAttributePricesProvider = new PriceAttributePricesProvider($this->helper);
    }

    public function testGetPrices()
    {
        /** @var PriceAttributePriceList|\PHPUnit_Framework_MockObject_MockObject $priceList **/
        $priceList = $this->createMock(PriceAttributePriceList::class);
        $priceList->expects($this->atLeastOnce())->method('getCurrencies')->willReturn(['USD', 'EUR']);

        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product **/
        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('getAvailableUnitCodes')
            ->willReturn(['set', 'item']);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects($this->once())->method('findBy')
            ->with(['product' => $product, 'priceList' => $priceList])->willReturn([
                $setUsd = $this->preparePrice('set', 'USD'),
                $setEur = $this->preparePrice('set', 'EUR'),
                $itemUsd = $this->preparePrice('item', 'USD'),
                $itemEur = $this->preparePrice('item', 'EUR'),
            ]);

        $this->helper->expects($this->once())->method('getEntityRepository')
            ->willReturn($entityRepository);

        $this->assertSame(
            [
                'set' => ['USD' => $setUsd, 'EUR' => $setEur],
                'item' => ['USD' => $itemUsd, 'EUR' => $itemEur],
            ],
            $this->priceAttributePricesProvider->getPrices($priceList, $product)
        );
    }

    /**
     * @param string $unitCode
     * @param string $currency
     * @return PriceAttributeProductPrice|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function preparePrice($unitCode, $currency)
    {
        $priceAttributeProductPrice = $this->createMock(PriceAttributeProductPrice::class);
        $priceAttributeProductPrice->expects($this->atLeastOnce())->method('getProductUnitCode')->willReturn($unitCode);

        $price = $this->createMock(Price::class);
        $price->expects($this->atLeastOnce())->method('getCurrency')->willReturn($currency);

        $priceAttributeProductPrice->expects($this->atLeastOnce())->method('getPrice')->willReturn($price);

        return $priceAttributeProductPrice;
    }
}
