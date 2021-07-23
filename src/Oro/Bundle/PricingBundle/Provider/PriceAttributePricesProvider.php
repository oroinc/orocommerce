<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;

class PriceAttributePricesProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param PriceAttributePriceList $priceList
     * @param Product $product
     * @return array
     */
    public function getPricesWithUnitAndCurrencies(PriceAttributePriceList $priceList, Product $product)
    {
        $data = [];
        /** @var PriceAttributeProductPrice[] $priceAttributePrices */
        $priceAttributePrices = $this->doctrineHelper
            ->getEntityRepository(PriceAttributeProductPrice::class)
            ->findBy(['product' => $product, 'priceList' => $priceList]);

        foreach ($product->getAvailableUnitCodes() as $unitCode) {
            $prices = [];
            foreach ($priceList->getCurrencies() as $currency) {
                $price = $this->findPrice($priceAttributePrices, $unitCode, $currency);
                if ($price) {
                    $prices[$currency] = $price;
                }
            }

            $data[$unitCode] = $prices;
        }

        return $data;
    }

    /**
     * @param PriceAttributeProductPrice[] $priceAttributePrices
     * @param string $unitCode
     * @param string $currency
     * @return PriceAttributeProductPrice|null
     */
    protected function findPrice(array $priceAttributePrices, $unitCode, $currency)
    {
        foreach ($priceAttributePrices as $price) {
            if ($price->getProductUnitCode() === $unitCode && $price->getPrice()->getCurrency() === $currency) {
                return $price;
            }
        }

        return null;
    }
}
