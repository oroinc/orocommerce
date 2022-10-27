<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\ProductBundle\Entity\Product;

class LoadPriceListToProducts extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'product' => 'product-1',
            'priceList' => 'price_list_1',
        ],
        [
            'product' => 'product-2',
            'priceList' => 'price_list_1',
        ],
        [
            'product' => 'product-2',
            'priceList' => 'price_list_2',
        ],
        [
            'product' => 'product-1',
            'priceList' => 'price_list_2',
        ],
        [
            'product' => 'product-3',
            'priceList' => 'price_list_1',
        ],
        [
            'product' => 'product-2',
            'priceList' => 'default_price_list',
        ],
        [
            'product' => 'product-3',
            'priceList' => 'default_price_list',
        ],
        [
            'product' => 'product-2',
            'priceList' => 'price_list_6',
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
                $priceList = $manager->getRepository(PriceList::class)->getDefault();
            } else {
                /** @var PriceList $priceList */
                $priceList = $this->getReference($data['priceList']);
            }

            $relation = new PriceListToProduct();
            $relation
                ->setPriceList($priceList)
                ->setProduct($product);

            $manager->persist($relation);
            $manager->flush($relation);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [];
    }
}
