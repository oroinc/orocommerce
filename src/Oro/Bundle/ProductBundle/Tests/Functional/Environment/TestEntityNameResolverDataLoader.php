<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Brand::class === $entityClass) {
            $brand = new Brand();
            $brand->setOrganization($repository->getReference('organization'));
            $brand->addName($this->createLocalizedFallbackValue($em, 'Test Brand'));
            $brand->addName($this->createLocalizedFallbackValue(
                $em,
                'Test Brand (de_DE)',
                $repository->getReference('de_DE')
            ));
            $brand->addName($this->createLocalizedFallbackValue(
                $em,
                'Test Brand (fr_FR)',
                $repository->getReference('fr_FR')
            ));
            $brand->setStatus('new');
            $repository->setReference('brand', $brand);
            $em->persist($brand);
            $em->flush();

            return ['brand'];
        }

        if (ProductUnit::class === $entityClass) {
            $productUnit = $em->find(ProductUnit::class, 'item');
            $repository->setReference('productUnit', $productUnit);
            $em->persist($productUnit);
            $em->flush();

            return ['productUnit'];
        }

        if (Product::class === $entityClass) {
            $product = new Product();
            $product->setOrganization($repository->getReference('organization'));
            $product->setSku('TEST_PRODUCT');
            $product->addName($this->createProductName($em, 'Test Product'));
            $product->addName($this->createProductName(
                $em,
                'Test Product (de_DE)',
                $repository->getReference('de_DE')
            ));
            $product->addName($this->createProductName(
                $em,
                'Test Product (fr_FR)',
                $repository->getReference('fr_FR')
            ));
            $product->setStatus('new');
            $repository->setReference('product', $product);
            $em->persist($product);

            $productWithEmptyName = new Product();
            $productWithEmptyName->setOrganization($repository->getReference('organization'));
            $productWithEmptyName->setSku('TEST_PRODUCT_1');
            $productWithEmptyName->addName($this->createProductName($em, ''));
            $productWithEmptyName->setStatus('new');
            $repository->setReference('productWithEmptyName', $productWithEmptyName);
            $em->persist($productWithEmptyName);

            $em->flush();

            return ['product', 'productWithEmptyName'];
        }

        if (ProductKitItem::class === $entityClass) {
            $product = new Product();
            $product->setOrganization($repository->getReference('organization'));
            $product->setSku('TEST_KIT_PRODUCT');
            $product->addName($this->createProductName($em, 'Test Kit Product'));
            $em->persist($product);
            $productKitItem = new ProductKitItem();
            $productKitItem->setProductKit($product);
            $productKitItem->addLabel($this->createProductKitItemLabel($em, 'Test Product Kit Item'));
            $productKitItem->addLabel($this->createProductKitItemLabel(
                $em,
                'Test Product Kit Item (de_DE)',
                $repository->getReference('de_DE')
            ));
            $productKitItem->addLabel($this->createProductKitItemLabel(
                $em,
                'Test Product Kit Item (fr_FR)',
                $repository->getReference('fr_FR')
            ));
            $repository->setReference('productKitItem', $productKitItem);
            $em->persist($productKitItem);
            $em->flush();

            return ['productKitItem'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Brand::class === $entityClass && 'brand' === $entityReference) {
            return 'Localization de_DE' === $locale
                ? 'Test Brand (de_DE)'
                : 'Test Brand';
        }
        if (ProductUnit::class === $entityClass) {
            return 'item';
        }
        if (Product::class === $entityClass) {
            if ('productWithEmptyName' === $entityReference) {
                return 'TEST_PRODUCT_1';
            }

            if (EntityNameProviderInterface::SHORT === $format) {
                return 'TEST_PRODUCT';
            }

            return 'Localization de_DE' === $locale
                ? 'Test Product (de_DE)'
                : 'Test Product';
        }
        if (ProductKitItem::class === $entityClass) {
            return 'Localization de_DE' === $locale
                ? 'Test Product Kit Item (de_DE)'
                : 'Test Product Kit Item';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function createLocalizedFallbackValue(
        EntityManagerInterface $em,
        string $value,
        ?Localization $localization = null
    ): LocalizedFallbackValue {
        $lfv = new LocalizedFallbackValue();
        $lfv->setString($value);
        if (null !== $localization) {
            $lfv->setLocalization($localization);
        }
        $em->persist($lfv);

        return $lfv;
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
