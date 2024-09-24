<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (ShoppingList::class === $entityClass) {
            $shoppingList = new ShoppingList();
            $shoppingList->setOrganization($repository->getReference('organization'));
            $shoppingList->setOwner($repository->getReference('user'));
            $shoppingList->setLabel('Test Shopping List');
            $repository->setReference('shoppingList', $shoppingList);
            $em->persist($shoppingList);
            $em->flush();

            return ['shoppingList'];
        }

        if (LineItem::class === $entityClass) {
            $shoppingList = new ShoppingList();
            $shoppingList->setOrganization($repository->getReference('organization'));
            $shoppingList->setOwner($repository->getReference('user'));
            $shoppingList->setLabel('Test Shopping List');
            $em->persist($shoppingList);
            $product = new Product();
            $product->setOrganization($repository->getReference('organization'));
            $product->setSku('SL_TEST_PRODUCT');
            $product->addName($this->createProductName($em, 'Test Shopping List Product'));
            $em->persist($product);
            $shoppingListLineItem = new LineItem();
            $shoppingListLineItem->setOrganization($repository->getReference('organization'));
            $shoppingListLineItem->setOwner($repository->getReference('user'));
            $shoppingListLineItem->setProduct($product);
            $shoppingListLineItem->setQuantity(1.2);
            $shoppingListLineItem->setUnit($em->find(ProductUnit::class, 'item'));
            $shoppingListLineItem->setChecksum('Test Checksum');
            $shoppingListLineItem->setNotes('Test Notes');
            $shoppingList->addLineItem($shoppingListLineItem);
            $repository->setReference('shoppingListLineItem', $shoppingListLineItem);
            $em->persist($shoppingListLineItem);
            $em->flush();

            return ['shoppingListLineItem'];
        }

        if (ProductKitItemLineItem::class === $entityClass) {
            $shoppingList = new ShoppingList();
            $shoppingList->setOrganization($repository->getReference('organization'));
            $shoppingList->setOwner($repository->getReference('user'));
            $shoppingList->setLabel('Test Shopping List');
            $em->persist($shoppingList);
            $product = new Product();
            $product->setOrganization($repository->getReference('organization'));
            $product->setSku('SLPK_TEST_PRODUCT');
            $product->addName($this->createProductName($em, 'Test Shopping List Kit Product'));
            $em->persist($product);
            $shoppingListLineItem = new LineItem();
            $shoppingListLineItem->setOrganization($repository->getReference('organization'));
            $shoppingListLineItem->setOwner($repository->getReference('user'));
            $shoppingListLineItem->setProduct($product);
            $shoppingListLineItem->setQuantity(1.2);
            $shoppingListLineItem->setUnit($em->find(ProductUnit::class, 'item'));
            $shoppingListLineItem->setChecksum('Test Checksum');
            $shoppingList->addLineItem($shoppingListLineItem);
            $em->persist($shoppingListLineItem);
            $productKitItem = new ProductKitItem();
            $productKitItem->setProductKit($product);
            $productKitItem->addLabel($this->createProductKitItemLabel($em, 'Test Product Kit Item'));
            $em->persist($productKitItem);
            $productKitItemLineItem = new ProductKitItemLineItem();
            $productKitItemLineItem->setKitItem($productKitItem);
            $productKitItemLineItem->setLineItem($shoppingListLineItem);
            $productKitItemLineItem->setProduct($product);
            $productKitItemLineItem->setQuantity(1.2);
            $productKitItemLineItem->setUnit($em->find(ProductUnit::class, 'item'));
            $productKitItemLineItem->setSortOrder(1);
            $repository->setReference('productKitItemLineItem', $productKitItemLineItem);
            $em->persist($productKitItemLineItem);
            $em->flush();

            return ['productKitItemLineItem'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (ShoppingList::class === $entityClass) {
            return 'Test Shopping List';
        }
        if (LineItem::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? (string)$repository->getReference($entityReference)->getId()
                : 'Test Checksum';
        }
        if (ProductKitItemLineItem::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? (string)$repository->getReference($entityReference)->getId()
                : sprintf('Item #%d', $repository->getReference($entityReference)->getId());
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function createProductName(
        EntityManagerInterface $em,
        string $value,
        ?Localization $localization = null
    ): ProductName {
        $name = new ProductName();
        $name->setString($value);
        if (null !== $localization) {
            $name->setLocalization($localization);
        }
        $em->persist($name);

        return $name;
    }

    private function createProductKitItemLabel(
        EntityManagerInterface $em,
        string $value,
        ?Localization $localization = null
    ): ProductKitItemLabel {
        $label = new ProductKitItemLabel();
        $label->setString($value);
        if (null !== $localization) {
            $label->setLocalization($localization);
        }
        $em->persist($label);

        return $label;
    }
}
