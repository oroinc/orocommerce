<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\TemplateFixture;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class PriceListFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'OroB2B\Bundle\PricingBundle\Entity\ProductPrice';
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
