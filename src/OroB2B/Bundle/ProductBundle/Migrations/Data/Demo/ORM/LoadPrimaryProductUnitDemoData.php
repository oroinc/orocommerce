<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use Doctrine\ORM\Mapping as ORM;

class LoadPrimaryProductUnitDemoData extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData',
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([]);
        foreach($products as $product){
            $precision = $manager->getRepository('OroB2BProductBundle:ProductUnitPrecision')
                                  ->findOneBy(["product" => $product->getId()], ['id' => 'ASC'], 1);
            $product->setPrimaryUnitPrecision($precision);
        }
        $manager->flush();
    }
}

