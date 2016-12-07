<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\MinimalProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

class LoadMinimalProductPrices extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected static $data = [
        // config
        [
            'product' => LoadProductData::PRODUCT_1,
            'cpl' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10,
            'currency' => 'USD'
        ],

        // website 1 (PriceListToWebsite, priceListsToAccountGroups)
        [
            'product' => LoadProductData::PRODUCT_1,
            'cpl' => '1t_2t_3t',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 11,
            'currency' => 'EUR'
        ],
        [
            'product' => LoadProductData::PRODUCT_1,
            'cpl' => '1t_2t_3t',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 13,
            'currency' => 'USD'
        ],
        // todo: fix in BB-4179
//        [
//            'product' => LoadProductData::PRODUCT_1,
//            'cpl' => '1t_2t_3t',
//            'qty' => 1,
//            'unit' => 'product_unit.box',
//            'price' => 11,
//            'currency' => 'EUR'
//        ],

        // website 1 (priceListsToAccounts)
        [
            'product' => LoadProductData::PRODUCT_1,
            'cpl' => '2t_3f_1t',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 12,
            'currency' => 'CA'
        ],

        // website 2 (PriceListToWebsite)
        [
            'product' => LoadProductData::PRODUCT_1,
            'cpl' => '2f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 20,
            'currency' => 'USD'
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

            /** @var CombinedPriceList $cpl */
            $cpl = $this->getReference($data['cpl']);

            /** @var ProductUnit $unit */
            $unit = $this->getReference($data['unit']);
            $price = Price::create($data['price'], $data['currency']);

            $productPrice = new MinimalProductPrice();
            $productPrice
                ->setPriceList($cpl)
                ->setUnit($unit)
                ->setQuantity($data['qty'])
                ->setPrice($price)
                ->setProduct($product);

            $manager->persist($productPrice);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCombinedPriceLists::class,
            LoadProductUnitPrecisions::class
        ];
    }
}
