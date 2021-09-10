<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\ProductResolvedCacheBuilder;

class ProductResolvedCacheBuilderBuildCacheTest extends WebTestCase
{
    private ProductResolvedCacheBuilder $cacheBuilder;

    private ScopeManager $scopeManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadCategoryVisibilityData::class,
            LoadProductVisibilityData::class,
        ]);

        $container = $this->getContainer();

        $productReindexManager = new ProductReindexManager(
            $container->get('event_dispatcher')
        );

        $indexScheduler = new ProductIndexScheduler(
            $container->get('oro_entity.doctrine_helper'),
            $productReindexManager
        );
        $this->scopeManager = $container->get('oro_scope.scope_manager');
        $this->cacheBuilder = new ProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_scope.scope_manager'),
            $indexScheduler,
            $container->get('oro_entity.orm.insert_from_select_query_executor'),
            $productReindexManager
        );
        $this->cacheBuilder->setCacheClass(ProductVisibilityResolved::class);
        $this->cacheBuilder->setRepository(
            $container->get('oro_visibility.product_repository')
        );
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
    }

    public function testBuildCache(): void
    {
        $repository = $this->getRepository();
        $manager = $this->getManager();
        $scope = $this->scopeManager->findOrCreate(ProductVisibility::VISIBILITY_TYPE);
        $product = $this->getReference(LoadProductData::PRODUCT_8);
        // new entities were generated
        $repository->createQueryBuilder('entity')
            ->delete('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved', 'entity')
            ->getQuery()
            ->execute();
        $this->assertResolvedEntitiesCount(0);
        $visibility = $manager->getRepository(ProductVisibility::class)
            ->findOneBy(['product' => $product, 'scope' => $scope]);
        $manager->remove($visibility);
        $manager->flush();

        $this->cacheBuilder->buildCache($scope);
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
        $this->cacheBuilder->buildCache();
        self::assertNull($repository->findOneBy(['scope' => $scope, 'product' => $firstProduct]));
    }

    protected function assertResolvedEntitiesCount(int $expected): void
    {
        $count = $this->getRepository()->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery()
            ->getSingleScalarResult();
        self::assertEquals($expected, $count);
    }

    protected function getManager(): EntityManager
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved');
    }

    protected function getRepository(): ProductRepository
    {
        return $this->getManager()->getRepository('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved');
    }
}
