<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

class LoadProductPrices extends AbstractFixture implements DependentFixtureInterface
{
    const PRODUCT_PRICE_1 = 'product_price.1';
    const PRODUCT_PRICE_2 = 'product_price.2';
    const PRODUCT_PRICE_3 = 'product_price.3';
    const PRODUCT_PRICE_4 = 'product_price.4';
    const PRODUCT_PRICE_5 = 'product_price.5';
    const PRODUCT_PRICE_6 = 'product_price.6';
    const PRODUCT_PRICE_7 = 'product_price.7';
    const PRODUCT_PRICE_8 = 'product_price.8';
    const PRODUCT_PRICE_9 = 'product_price.9';
    const PRODUCT_PRICE_10 = 'product_price.10';
    const PRODUCT_PRICE_11 = 'product_price.11';
    const PRODUCT_PRICE_12 = 'product_price.12';
    const PRODUCT_PRICE_13 = 'product_price.13';
    const PRODUCT_PRICE_14 = 'product_price.14';
    const PRODUCT_PRICE_15 = 'product_price.15';
    const PRODUCT_PRICE_16 = 'product_price.16';

    /**
     * @var array
     */
    protected $loadedRelations = [];

    /**
     * @var array
     */
    protected $data = [
        [
            'product' => 'product.1',
            'priceList' => 'price_list_1',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => self::PRODUCT_PRICE_1
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_list_1',
            'qty' => 11,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => self::PRODUCT_PRICE_2
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_1',
            'qty' => 12,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => self::PRODUCT_PRICE_3
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_2',
            'qty' => 13,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => self::PRODUCT_PRICE_4
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_2',
            'qty' => 14,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => self::PRODUCT_PRICE_5
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_list_2',
            'qty' => 15,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => self::PRODUCT_PRICE_6
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_list_1',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10,
            'currency' => 'USD',
            'reference' => self::PRODUCT_PRICE_7
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_1',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 20,
            'currency' => 'USD',
            'reference' => self::PRODUCT_PRICE_8
        ],
        [
            'product' => 'product.3',
            'priceList' => 'price_list_1',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 5,
            'currency' => 'USD',
            'reference' => self::PRODUCT_PRICE_9
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_list_1',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => self::PRODUCT_PRICE_10
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_1',
            'qty' => 14,
            'unit' => 'product_unit.liter',
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => self::PRODUCT_PRICE_11
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_2',
            'qty' => 24,
            'unit' => 'product_unit.bottle',
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => self::PRODUCT_PRICE_12
        ],
        [
            'product' => 'product.2',
            'priceList' => 'default_price_list',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 17.5,
            'currency' => 'EUR',
            'reference' => self::PRODUCT_PRICE_13
        ],
        [
            'product' => 'product.3',
            'priceList' => 'default_price_list',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 20.5,
            'currency' => 'EUR',
            'reference' => self::PRODUCT_PRICE_14
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_6',
            'qty' => 97,
            'unit' => 'product_unit.liter',
            'price' => 15,
            'currency' => 'USD',
            'reference' => self::PRODUCT_PRICE_15
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_6',
            'qty' => 97,
            'unit' => 'product_unit.bottle',
            'price' => 15,
            'currency' => 'EUR',
            'reference' => self::PRODUCT_PRICE_16
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
            if ($data['priceList'] === 'default_price_list') {
                $priceList = $manager->getRepository('OroB2BPricingBundle:PriceList')->getDefault();
            } else {
                /** @var PriceList $priceList */
                $priceList = $this->getReference($data['priceList']);
            }
            /** @var ProductUnit $unit */
            $unit = $this->getReference($data['unit']);
            $price = Price::create($data['price'], $data['currency']);

            $productPrice = new ProductPrice();
            $productPrice
                ->setPriceList($priceList)
                ->setUnit($unit)
                ->setQuantity($data['qty'])
                ->setPrice($price)
                ->setProduct($product);

            $manager->persist($productPrice);
            $this->setReference($data['reference'], $productPrice);
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
            LoadPriceLists::class
        ];
    }
}
