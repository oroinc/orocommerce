<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadPriceListToProductWithoutPrices extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $relations = [
        [
            'priceList' => 'price_list_2',
            'product' => 'product.3'
        ],
        [
            'priceList' => 'price_list_2',
            'product' => 'product.4'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->relations as $relationData) {
            /** @var PriceList $priceList */
            $priceList = $this->getReference($relationData['priceList']);
            /** @var Product $product */
            $product = $this->getReference($relationData['product']);

            $relation = new PriceListToProduct();
            $relation->setPriceList($priceList)
                ->setProduct($product)
                ->setManual(true);
            $manager->persist($relation);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
        ];
    }
}
