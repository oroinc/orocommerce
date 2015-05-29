<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProducts extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createProduct($manager, 'product.1');
        $this->createProduct($manager, 'product.2');

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
