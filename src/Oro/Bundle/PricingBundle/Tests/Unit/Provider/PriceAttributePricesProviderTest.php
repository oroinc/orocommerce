<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class PriceAttributePricesProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var PriceAttributePricesProvider
     */
    protected $priceAttributePricesProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->priceAttributePricesProvider = new PriceAttributePricesProvider($this->doctrineHelper);
    }

    public function testGetPricesWithUnitAndCurrencies()
    {
        $priceList = new PriceAttributePriceList();
        $priceList->setCurrencies(['USD', 'EUR', 'GBP']);

        $itemUnitPrecision = $this->createProductUnitPrecision('set');
        $setUnitPrecision = $this->createProductUnitPrecision('item');

        $product = new Product();
        $product->addUnitPrecision($setUnitPrecision);
        $product->addUnitPrecision($itemUnitPrecision);

        $setUsd = $this->getPriceAttributeProductPrice('set', 'USD');
        $setEur = $this->getPriceAttributeProductPrice('set', 'EUR');
        $itemUsd = $this->getPriceAttributeProductPrice('item', 'USD');
        $itemEur = $this->getPriceAttributeProductPrice('item', 'EUR');

        $priceAttributeProductPriceRepository = $this->createMock(EntityRepository::class);
        $priceAttributeProductPriceRepository->expects($this->once())->method('findBy')
            ->with(['product' => $product, 'priceList' => $priceList])->willReturn([
                $setUsd,
                $setEur,
                $itemUsd,
                $itemEur,
            ]);

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')
            ->with(PriceAttributeProductPrice::class)
            ->willReturn($priceAttributeProductPriceRepository);

        $this->assertEquals(
            [
                'set' => ['USD' => $setUsd, 'EUR' => $setEur],
                'item' => ['USD' => $itemUsd, 'EUR' => $itemEur],
            ],
            $this->priceAttributePricesProvider->getPricesWithUnitAndCurrencies($priceList, $product)
        );
    }

    /**
     * @param string $unitCode
     * @param string $currency
     * @return PriceAttributeProductPrice
     */
    protected function getPriceAttributeProductPrice($unitCode, $currency)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        $price = Price::create(10, $currency);
        $price->setCurrency($currency);

        $priceAttributeProductPrice = new PriceAttributeProductPrice();
        $priceAttributeProductPrice->setUnit($productUnit);
        $priceAttributeProductPrice->setPrice($price);

        return $priceAttributeProductPrice;
    }

    /**
     * @param string $code
     * @return ProductUnitPrecision
     */
    private function createProductUnitPrecision($code)
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);

        return $unitPrecision;
    }
}
