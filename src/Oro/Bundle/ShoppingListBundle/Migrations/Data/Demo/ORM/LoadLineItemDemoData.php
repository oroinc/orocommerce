<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData',
            'Oro\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM\LoadShoppingListDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $customerUser = $manager->getRepository('OroCustomerBundle:CustomerUser')->findOneBy([]);
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroShoppingListBundle/Migrations/Data/Demo/ORM/data/shopping_lists.csv');

        /** @var User $user */
        $owner = $manager->getRepository('OroUserBundle:User')->findOneBy([]);

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

        $products = $manager->getRepository('OroProductBundle:Product')->findBy([], null, rand(5, 15));
        $chunkedProducts = array_chunk($products, ceil(count($products) / count($labels)));
        $shoppingListRepository = $manager->getRepository('OroShoppingListBundle:ShoppingList');

        foreach ($labels as $index => $shoppingListLabel) {
            $shoppingList = $shoppingListRepository->findOneBy(['label' => $shoppingListLabel]);

            /** @var Product $product */
            foreach ($chunkedProducts[$index] as $id => $product) {
                $lineItem = (new LineItem())
                    ->setCustomerUser($customerUser)
                    ->setOrganization($customerUser->getOrganization())
                    ->setOwner($owner)
                    ->setShoppingList($shoppingList)
                    ->setProduct($product)
                    ->setQuantity(mt_rand(1, 10))
                    ->setUnit($product->getUnitPrecisions()->current()->getUnit());
                $shoppingList->addLineItem($lineItem);

                $manager->persist($lineItem);
            }
        }

        $manager->flush();
    }
}
