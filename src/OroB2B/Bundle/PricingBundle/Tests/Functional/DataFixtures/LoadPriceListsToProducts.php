<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadPriceListsToProducts extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'product' => 'product.2',
            'priceList' => 'default_price_list',
            'manual' => false,
            'reference' => 'default_price_list_product_2'
        ],
        [
            'product' => 'product.3',
            'priceList' => 'default_price_list',
            'manual' => true,
            'reference' => 'default_price_list_product_3'
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_list_1',
            'manual' => true,
            'reference' => 'price_list_1_product_1'
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_1',
            'manual' => true,
            'reference' => 'price_list_1_product_2'
        ],
        [
            'product' => 'product.1',
            'priceList' => 'price_list_2',
            'manual' => true,
            'reference' => 'price_list_2_product_1'
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_2',
            'manual' => false,
            'reference' => 'price_list_2_product_2'
        ],
        [
            'product' => 'product.3',
            'priceList' => 'price_list_1',
            'manual' => true,
            'reference' => 'price_list_1_product_3'
        ],
        [
            'product' => 'product.2',
            'priceList' => 'price_list_6',
            'manual' => false,
            'reference' => 'price_list_6_product_2'
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

            if ($data['priceList'] === 'default_price_list') {
                $priceList = $manager->getRepository('OroB2BPricingBundle:PriceList')->getDefault();
            } else {
                /** @var PriceList $priceList */
                $priceList = $this->getReference($data['priceList']);
            }

            $priceListToProduct = new PriceListToProduct();
            $priceListToProduct
                ->setPriceList($priceList)
                ->setProduct($product)
                ->setManual($data['manual']);

            $manager->persist($priceListToProduct);
            $this->setReference($data['reference'], $priceListToProduct);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists'
        ];
    }
}
