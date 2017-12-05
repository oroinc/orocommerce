<?php

namespace Oro\Bundle\PricingBundle\ImportExport\TemplateFixture;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class PriceAttributeProductPriceFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    const PRODUCT_SKU = 'sku001';
    const PRICE_ATTRIBUTE = 'MSRP';
    const UNIT_CODE = 'item';
    const CURRENCY = 'USD';
    const PRICE = 10.89;

    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return $this->getEntityData('Example Product Price Attribute Price');
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return PriceAttributeProductPrice::class;
    }

    /**
     * {@inheritDoc}
     */
    public function createEntity($key)
    {
        return new PriceAttributeProductPrice();
    }

    /**
     * {@inheritDoc}
     *
     * @param PriceAttributeProductPrice $entity
     */
    public function fillEntityData($key, $entity)
    {
        $product = new Product();
        $product->setSku(self::PRODUCT_SKU);

        $priceList = new PriceAttributePriceList();
        $priceList->setName(self::PRICE_ATTRIBUTE);

        $unit = new ProductUnit();
        $unit->setCode(self::UNIT_CODE);

        $price = new Price();
        $price
            ->setCurrency(self::CURRENCY)
            ->setValue(self::PRICE);

        $entity
            ->setProduct($product)
            ->setPriceList($priceList)
            ->setUnit($unit)
            ->setPrice($price);
    }
}
