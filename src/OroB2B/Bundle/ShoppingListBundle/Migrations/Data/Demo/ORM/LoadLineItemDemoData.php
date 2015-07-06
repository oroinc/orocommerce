<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadLineItemDemoData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData',
            'OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData',
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData',
            'OroB2B\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM\LoadShoppingListDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $shoppingLists = $manager->getRepository('OroB2BShoppingListBundle:ShoppingList')->findAll();
        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([], null, rand(0, 30));
        $chunkedProducts = array_chunk($products, ceil(count($products) / count($shoppingLists)));

        foreach ($shoppingLists as $index => $shoppingList) {
            /** @var Product $product */
            foreach ($chunkedProducts[$index] as $id => $product) {
                $lineItem = (new LineItem())
                    ->setShoppingList($shoppingList)
                    ->setNotes(sprintf('Line item %d notes', $id + 1))
                    ->setProduct($product)
                    ->setQuantity(mt_rand(1, 25))
                    ->setUnit($product->getUnitPrecisions()->current()->getUnit());

                $manager->persist($lineItem);
                $manager->flush();
            }
        }
    }
}
