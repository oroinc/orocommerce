<?php

namespace Oro\Bundle\PricingBundle\ImportExport\TemplateFixture;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class PriceListFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Oro\Bundle\PricingBundle\Entity\ProductPrice';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->getEntityData('Example Price List');
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new ProductPrice();
    }

    /**
     * @param string  $key
     * @param ProductPrice $entity
     */
    public function fillEntityData($key, $entity)
    {
        $entity
            ->setProduct($this->createProduct())
            ->setPrice($this->createPrice())
            ->setUnit($this->createProductUnit())
            ->setQuantity(42);
    }

    /**
     * @return Product
     */
    protected function createProduct()
    {
        $product = new Product();
        $product->setSku('sku_001');

        return $product;
    }

    /**
     * @return Price
     */
    protected function createPrice()
    {
        $price = new Price();
        $price->setValue(100);
        $price->setCurrency('USD');

        return $price;
    }

    /**
     * @return ProductUnit
     */
    protected function createProductUnit()
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        return $productUnit;
    }
}
