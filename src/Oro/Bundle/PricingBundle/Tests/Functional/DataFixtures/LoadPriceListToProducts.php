<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;

class LoadPriceListToProducts extends AbstractFixture
{
    private array $data = [
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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $data) {
            $relation = new PriceListToProduct();
            $relation->setPriceList($this->getReference($data['priceList']));
            $relation->setProduct($this->getReference($data['product']));
            $manager->persist($relation);
            $manager->flush($relation);
        }
        $manager->flush();
    }
}
