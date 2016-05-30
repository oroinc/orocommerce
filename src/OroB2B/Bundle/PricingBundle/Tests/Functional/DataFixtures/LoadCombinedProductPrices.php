<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class LoadCombinedProductPrices extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected static $data = [
        [
            'product' => 'product.1',
            'priceList' => '1f',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.1'
        ],
        [
            'product' => 'product.1',
            'priceList' => '1f',
            'qty' => 11,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => 'product_price.2'
        ],
        [
            'product' => 'product.2',
            'priceList' => '1f',
            'qty' => 12,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.3'
        ],
        [
            'product' => 'product.2',
            'priceList' => '2f',
            'qty' => 13,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.4'
        ],
        [
            'product' => 'product.2',
            'priceList' => '2f',
            'qty' => 14,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.5'
        ],
        [
            'product' => 'product.1',
            'priceList' => '2f',
            'qty' => 15,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.6'
        ],
        [
            'product' => 'product.1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10,
            'currency' => 'USD',
            'reference' => 'product_price.7'
        ],
        [
            'product' => 'product.2',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 20,
            'currency' => 'USD',
            'reference' => 'product_price.8'
        ],
        [
            'product' => 'product.3',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 5,
            'currency' => 'USD',
            'reference' => 'product_price.9'
        ],
        [
            'product' => 'product.1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => 'product_price.10'
        ],
        [
            'product' => 'product.2',
            'priceList' => '1f',
            'qty' => 14,
            'unit' => 'product_unit.liter',
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => 'product_price.11'
        ],
        [
            'product' => 'product.2',
            'priceList' => '2f',
            'qty' => 24,
            'unit' => 'product_unit.bottle',
            'price' => 16.5,
            'currency' => 'EUR',
            'reference' => 'product_price.12'
        ],
        [
            'product' => 'product.1',
            'priceList' => '1t_2t_3t',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 1.1,
            'currency' => 'USD',
            'reference' => 'product_price.13'
        ],
        [
            'product' => 'product.1',
            'priceList' => '1t_2t_3t',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'price' => 1.2,
            'currency' => 'USD',
            'reference' => 'product_price.14'
        ],
        [
            'product' => 'product.4',
            'priceList' => '1f',
            'qty' => 10,
            'unit' => 'product_unit.bottle',
            'price' => 200.5,
            'currency' => 'USD',
            'reference' => 'product_price.15'
        ],
        [
            'product' => 'product.5',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 0,
            'currency' => 'USD',
            'reference' => 'product_price.16'
        ],
        [
            'product' => 'product.1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'price' => 13.1,
            'currency' => 'USD',
            'reference' => 'product_price.17'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (static::$data as $data) {
            /** @var Product $product */
            $product = $this->getReference($data['product']);

            /** @var PriceList $priceList */
            $priceList = $this->getReference($data['priceList']);

            /** @var ProductUnit $unit */
            $unit = $this->getReference($data['unit']);
            $price = Price::create($data['price'], $data['currency']);

            $productPrice = new CombinedProductPrice();
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
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists'
        ];
    }
}
