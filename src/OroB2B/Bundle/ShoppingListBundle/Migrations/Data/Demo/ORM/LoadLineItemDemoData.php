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
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BShoppingListBundle/Migrations/Data/Demo/ORM/data/shopping_lists.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        $labels = [];
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $labels[] = $row['label'];
        }
        fclose($handler);

        if (count($labels) === 0) {
            return;
        }

        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([], null, rand(5, 15));
        $chunkedProducts = array_chunk($products, ceil(count($products) / count($labels)));
        $shoppingListRepository = $manager->getRepository('OroB2BShoppingListBundle:ShoppingList');

        foreach ($labels as $index => $shoppingListLabel) {
            $shoppingList = $shoppingListRepository->findOneBy(['label' => $shoppingListLabel]);

            /** @var Product $product */
            foreach ($chunkedProducts[$index] as $id => $product) {
                $lineItem = (new LineItem())
                    ->setShoppingList($shoppingList)
                    ->setNotes(sprintf('Line item %d notes', $id + 1))
                    ->setProduct($product)
                    ->setQuantity(mt_rand(1, 25))
                    ->setUnit($product->getUnitPrecisions()->current()->getUnit());

                $manager->persist($lineItem);
            }
        }

        $manager->flush();
    }
}
