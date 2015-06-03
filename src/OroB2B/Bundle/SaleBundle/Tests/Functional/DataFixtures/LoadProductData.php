<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductData extends AbstractFixture
{
    const PRODUCT1  = 'sale-sku1';
    const PRODUCT2  = 'sale-sku2';

    /**
     * @var array
     */
    protected $products = [
        [
            'sku' => self::PRODUCT1,
        ],
        [
            'sku' => self::PRODUCT2,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $em = $this->entityManager;

        foreach ($this->products as $item) {
            /* @var $product Product */
            $product = new Product();

            $product
                ->setSku($item['sku'])
            ;

            $em->persist($product);
            $em->flush();
        }
    }
}
