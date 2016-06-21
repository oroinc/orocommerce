<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

class LoadPriceAttributeProductPrices extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'product' => 'product.1',
            'priceList' => 'price_attribute_price_list_1',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.1',
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_attribute_price_list_1',
            'qty' => 11,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.2',
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_attribute_price_list_1',
            'qty' => 12,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.3',
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_attribute_price_list_2',
            'qty' => 13,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.4',
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_attribute_price_list_2',
            'qty' => 14,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.5',
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_attribute_price_list_2',
            'qty' => 15,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.6',
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_attribute_price_list_1',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.7',
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_attribute_price_list_1',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 20,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.8',
        ],
        [
            'product' => 'product.3',
            'priceList' => 'price_attribute_price_list_1',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 5,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.9',
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_attribute_price_list_1',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.10',
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_attribute_price_list_1',
            'qty' => 14,
            'unit' => 'product_unit.liter',
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.11',
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_attribute_price_list_2',
            'qty' => 24,
            'unit' => 'product_unit.bottle',
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.12',
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_attribute_price_list_6',
            'qty' => 97,
            'unit' => 'product_unit.liter',
            'price' => 15,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.15',
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_attribute_price_list_6',
            'qty' => 97,
            'unit' => 'product_unit.bottle',
            'price' => 15,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.16',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            /** @var Product $product */
            $product = $this->getReference($data['product']);

            /** @var PriceList $priceList */
            $priceList = $this->getReference($data['priceList']);
            /** @var ProductUnit $unit */
            $unit = $this->getReference($data['unit']);
            $price = Price::create($data['price'], $data['currency']);

            $priceAttributeProductPrice = new PriceAttributeProductPrice();
            $priceAttributeProductPrice
                ->setPriceList($priceList)
                ->setUnit($unit)
                ->setQuantity($data['qty'])
                ->setPrice($price)
                ->setProduct($product);

            $manager->persist($priceAttributeProductPrice);
            $this->setReference($data['reference'], $priceAttributeProductPrice);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductUnitPrecisions::class,
            LoadPriceAttributePriceLists::class,
        ];
    }
}
