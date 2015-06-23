<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductFieldsDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue[] $inventoryStatuses */
        $inventoryStatuses = $manager->getRepository($inventoryStatusClassName)->findAll();

        $visibilityClassName = ExtendHelper::buildEnumValueClassName('prod_visibility');
        /** @var AbstractEnumValue[] $visibilities */
        $visibilities = $manager->getRepository($visibilityClassName)->findAll();

        $products = $manager->getRepository('OroB2BProductBundle:Product')->findAll();

        foreach ($products as $product) {
            $product
                ->setInventoryStatus($inventoryStatuses[array_rand($inventoryStatuses)])
                ->setVisibility($visibilities[array_rand($visibilities)])
            ;
        }

        $manager->flush();
    }
}
