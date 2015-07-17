<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class LoadProductPrices extends AbstractFixture implements DependentFixtureInterface
{
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
            'reference' => 'product_price.1'
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_list_1',
            'qty' => 11,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'EUR',
            'reference' => 'product_price.2'
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_1',
            'qty' => 12,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.3'
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_2',
            'qty' => 13,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.4'
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_2',
            'qty' => 14,
            'unit' => 'product_unit.bottle',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.5'
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_list_2',
            'qty' => 15,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.6'
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_list_1',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 12.5,
            'currency' => 'USD',
            'reference' => 'product_price.7'
        ]
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
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists'
        ];
    }
}
