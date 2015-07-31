<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProducts extends AbstractFixture
{
    const PRODUCT_1 = 'product.1';
    const PRODUCT_2 = 'product.2';
    const PRODUCT_3 = 'product.3';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createProduct($manager, self::PRODUCT_1);
        $this->createProduct($manager, self::PRODUCT_2);
        $this->createProduct($manager, self::PRODUCT_3);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $sku
     * @return Product
     */
    protected function createProduct(ObjectManager $manager, $sku)
    {
        $businessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->getFirst();
        $organization = $manager
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();

        $product = new Product();
        $product->setSku($sku);
        $product->setOwner($businessUnit);
        $product->setOrganization($organization);
        $manager->persist($product);
        $this->addReference($sku, $product);

        return $product;
    }
}
