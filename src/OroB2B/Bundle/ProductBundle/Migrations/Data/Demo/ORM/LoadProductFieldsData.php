<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductFieldsDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string[]
     */
    protected static $inventoryStatuses = [
        Product::INVENTORY_STATUS_IN_STOCK,
        Product::INVENTORY_STATUS_OUT_OF_STOCK,
        Product::INVENTORY_STATUS_DISCONTINUED,
    ];

    /**
     * @var string[]
     */
    protected static $visibilityOptions = [
        Product::VISIBILITY_BY_CONFIG,
        Product::VISIBILITY_VISIBLE,
        Product::VISIBILITY_NOT_VISIBLE,
    ];

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

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
        $inventoryStatusRepository = $manager->getRepository($inventoryStatusClassName);

        $visibilityClassName = ExtendHelper::buildEnumValueClassName('prod_visibility');
        $visibilityRepository = $manager->getRepository($visibilityClassName);

        $products = $manager->getRepository('OroB2BProductBundle:Product')->findAll();

        foreach ($products as $product) {
            $randomInventoryStatusId = self::$inventoryStatuses[array_rand(self::$inventoryStatuses)];
            $randomVisibility = self::$visibilityOptions[array_rand(self::$visibilityOptions)];
            /** @var AbstractEnumValue $inventoryStatus */
            $inventoryStatus = $inventoryStatusRepository->find($randomInventoryStatusId);
            /** @var AbstractEnumValue $visibility */
            $visibility = $visibilityRepository->find($randomVisibility);
            $product
                ->setInventoryStatus($inventoryStatus)
                ->setVisibility($visibility)
            ;
        }

        $manager->flush();
    }
}
