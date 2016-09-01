<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

class LoadPriceAttributeProductPrices extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        // PRICE_ATTRIBUTE_PRICE_LIST_1
        [
            'product' => LoadProductData::PRODUCT_1,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'qty' => 1,
            'unit' => LoadProductUnits::LITER,
            'price' => 11,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.1',
        ],
        [
            'product' => LoadProductData::PRODUCT_1,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'qty' => 1,
            'unit' => LoadProductUnits::LITER,
            'price' => 10,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.2',
        ],
        [
            'product' => LoadProductData::PRODUCT_1,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'qty' => 1,
            'unit' => LoadProductUnits::BOTTLE,
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.3',
        ],
        [
            'product' => LoadProductData::PRODUCT_1,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'qty' => 1,
            'unit' => LoadProductUnits::BOTTLE,
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.4',
        ],
        [
            'product' => LoadProductData::PRODUCT_2,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'qty' => 1,
            'unit' => LoadProductUnits::LITER,
            'price' => 20,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.5',
        ],
        [
            'product' => LoadProductData::PRODUCT_2,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'qty' => 1,
            'unit' => LoadProductUnits::BOTTLE,
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.6',
        ],
        [
            'product' => LoadProductData::PRODUCT_2,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'qty' => 1,
            'unit' => LoadProductUnits::BOTTLE,
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.7',
        ],
        [
            'product' => LoadProductData::PRODUCT_3,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1,
            'qty' => 1,
            'unit' => LoadProductUnits::LITER,
            'price' => 5,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.8',
        ],

        // PRICE_ATTRIBUTE_PRICE_LIST_2
        [
            'product' => LoadProductData::PRODUCT_1,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2,
            'qty' => 1,
            'unit' => LoadProductUnits::LITER,
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.9',
        ],
        [
            'product' => LoadProductData::PRODUCT_2,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2,
            'qty' => 1,
            'unit' => LoadProductUnits::LITER,
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.10',
        ],
        [
            'product' => LoadProductData::PRODUCT_2,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2,
            'qty' => 1,
            'unit' => LoadProductUnits::BOTTLE,
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.11',
        ],
        [
            'product' => LoadProductData::PRODUCT_2,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2,
            'qty' => 1,
            'unit' => LoadProductUnits::BOTTLE,
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.12',
        ],

        // PRICE_ATTRIBUTE_PRICE_LIST_6
        [
            'product' => LoadProductData::PRODUCT_2,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_6,
            'qty' => 1,
            'unit' => LoadProductUnits::LITER,
            'price' => 15,
            'currency' => 'USD',
            'reference' => 'price_attribute_product_price.13',
        ],
        [
            'product' => LoadProductData::PRODUCT_2,
            'priceList' => LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_6,
            'qty' => 1,
            'unit' => LoadProductUnits::BOTTLE,
            'price' => 15,
            'currency' => 'EUR',
            'reference' => 'price_attribute_product_price.14',
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
