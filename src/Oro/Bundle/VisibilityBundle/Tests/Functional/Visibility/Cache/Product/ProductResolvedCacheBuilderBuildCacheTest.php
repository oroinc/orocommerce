<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\ProductResolvedCacheBuilder;

class ProductResolvedCacheBuilderBuildCacheTest extends WebTestCase
{
    private ProductResolvedCacheBuilder $builder;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadCategoryVisibilityData::class, LoadProductVisibilityData::class]);

        $this->builder = self::getContainer()->get(
            'oro_visibility.visibility.cache.product.product_resolved_cache_builder'
        );

        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
    }

    public function testBuildCache(): void
    {
        $repository = self::getContainer()->get('doctrine')->getRepository(ProductVisibilityResolved::class);
        $manager = self::getContainer()->get('doctrine')->getManagerForClass(ProductVisibilityResolved::class);
        $scope = self::getContainer()->get('oro_scope.scope_manager')->findOrCreate(
            ProductVisibility::VISIBILITY_TYPE
        );
        $product = $this->getReference(LoadProductData::PRODUCT_8);
        // new entities were generated
        $repository->createQueryBuilder('entity')
            ->delete(ProductVisibilityResolved::class, 'entity')
            ->getQuery()
            ->execute();
        $this->assertResolvedEntitiesCount(0);
        $visibility = $manager->getRepository(ProductVisibility::class)
            ->findOneBy(['product' => $product, 'scope' => $scope]);
        $manager->remove($visibility);
        $manager->flush();

        $this->builder->buildCache($scope);
        $this->assertResolvedEntitiesCount(4);

        // config fallback
        /** @var Product $firstProduct */
        $firstProduct = $this->getReference(LoadProductData::PRODUCT_1);

        self::assertNull($repository->findOneBy(['scope' => $scope, 'product' => $firstProduct]));

        // category fallback
        /** @var ProductVisibilityResolved $productVisibility */
        $productVisibility = $repository->findOneBy([
            'scope' => $scope,
            'product' => $this->getReference(LoadProductData::PRODUCT_8)
        ]);
        self::assertNotNull($productVisibility);
        self::assertEquals(
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            $productVisibility->getVisibility()
        );
        self::assertEquals(
            $this->getReference(LoadCategoryData::FOURTH_LEVEL2),
            $productVisibility->getCategory()
        );

        // static fallback
        $productVisibility = $repository
            ->findOneBy(['scope' => $scope, 'product' => $this->getReference(LoadProductData::PRODUCT_4)]);
        self::assertNotNull($productVisibility);
        self::assertEquals(
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            $productVisibility->getVisibility()
        );
        self::assertNull($productVisibility->getCategory());

        // invalid entity for first product in default scope
        $resolvedVisibility = new ProductVisibilityResolved($scope, $firstProduct);
        $resolvedVisibility->setVisibility(BaseProductVisibilityResolved::VISIBILITY_VISIBLE)
            ->setSource(BaseProductVisibilityResolved::SOURCE_STATIC);
        $manager->persist($resolvedVisibility);
        $manager->flush();

        // invalid entities were removed
        self::assertNotNull($repository->findOneBy(['scope' => $scope, 'product' => $firstProduct]));
        $this->builder->buildCache();
        self::assertNull($repository->findOneBy(['scope' => $scope, 'product' => $firstProduct]));
    }

    private function assertResolvedEntitiesCount(int $expected): void
    {
        $count = self::getContainer()->get('doctrine')->getRepository(ProductVisibilityResolved::class)
            ->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery()
            ->getSingleScalarResult();
        self::assertEquals($expected, $count);
    }
}
