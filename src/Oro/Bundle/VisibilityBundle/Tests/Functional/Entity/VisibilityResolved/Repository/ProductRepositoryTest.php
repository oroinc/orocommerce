<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository as ProductEntityRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\ResolvedEntityRepositoryTestTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductRepositoryTest extends WebTestCase
{
    use ResolvedEntityRepositoryTestTrait ;

    private EntityManager $entityManager;
    private ProductRepository $repository;
    private ScopeManager $scopeManager;
    private InsertFromSelectQueryExecutor $insertExecutor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $this->insertExecutor = $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');

        $this->entityManager = $this->getResolvedVisibilityManager();
        $this->repository = $this->getContainer()
            ->get('oro_visibility.product_repository');

        $this->loadFixtures([LoadProductVisibilityData::class]);
    }

    public function testClearTableByScope()
    {
        $scope = $this->getScope();
        $visibilitiesCount = count($this->repository->findBy(['scope' => $scope]));

        $deleted = $this->repository->clearTable($scope);
        $actual = $this->repository->findBy(['scope' => $scope]);

        $this->assertEmpty($actual);
        $this->assertSame($visibilitiesCount, $deleted);
    }

    public function testFindByPrimaryKey()
    {
        /** @var ProductVisibilityResolved $actualEntity */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $scope = $this->getScope();
        $entity = new ProductVisibilityResolved($scope, $product);
        $this->getResolvedVisibilityManager()->persist($entity);
        $this->getResolvedVisibilityManager()->flush();

        $actualEntity = $this->repository->findOneBy([]);
        $expectedEntity = $this->repository->findByPrimaryKey(
            $actualEntity->getProduct(),
            $actualEntity->getScope()
        );

        $this->assertEquals(spl_object_hash($expectedEntity), spl_object_hash($actualEntity));
    }

    public function testClearTable()
    {
        $this->repository->clearTable();
        $actual = $this->repository->findAll();

        $this->assertEmpty($actual);
    }

    public function testInsertFromBaseTable()
    {
        $this->repository->clearTable();

        $this->repository->insertStatic($this->insertExecutor);
        $actual = $this->getActualArray();

        $this->assertCount(3, $actual);
        $this->assertInsertedFromBaseTable($actual);
    }

    public function testInsertByCategory()
    {
        $this->repository->clearTable();

        $qb = $this->getContainer()->get('doctrine')
            ->getRepository(ProductVisibility::class)
            ->createQueryBuilder('visibility');
        $qb->delete(ProductVisibility::class, 'visibility')->getQuery()->execute();

        $scope = $this->getScope();
        $categoryScope = $this->scopeManager->findOrCreate('category_visibility', $scope);

        $this->repository->insertByCategory($this->insertExecutor, $scope, $categoryScope);

        $actual = $this->getActualArray();

        $this->assertCount(8, $actual);
        $this->assertInsertedByCategory($actual);
    }

    public function testInsertUpdateDeleteAndHasEntity()
    {
        $scope = $this->getScope();
        $categoryScope = $this->scopeManager->findOrCreate('category_visibility', $scope);
        $this->repository->clearTable();
        $this->repository->insertStatic($this->insertExecutor);
        $this->repository->insertByCategory($this->insertExecutor, $scope, $categoryScope);

        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $where = ['product' => $product, 'scope' => $scope];
        $this->assertTrue($this->repository->hasEntity($where));

        $this->assertDelete($this->repository, $where);
        $this->assertInsert(
            $this->entityManager,
            $this->repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            BaseProductVisibilityResolved::SOURCE_STATIC,
            $scope
        );
        $this->assertUpdate(
            $this->entityManager,
            $this->repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            BaseProductVisibilityResolved::SOURCE_CATEGORY
        );
    }

    private function resolveVisibility(string $visibility): ?int
    {
        if (ProductVisibility::HIDDEN === $visibility) {
            return BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
        }
        if (ProductVisibility::VISIBLE === $visibility) {
            return BaseProductVisibilityResolved::VISIBILITY_VISIBLE;
        }

        return null;
    }

    private function assertInsertedByCategory(array $actual): void
    {
        $categoryRepository = $this->getCategoryRepository();
        $pv = $this->getProductVisibilities();
        $products = $this->getProducts();
        $scope = $this->getScope();
        foreach ($products as $product) {
            if (array_filter($pv, function (ProductVisibility $item) use ($product) {
                return $product === $item->getProduct();
            })) {
                continue;
            }

            $category = $categoryRepository->findOneByProduct($product);
            if (!$category) {
                continue;
            }

            $expected = [
                'scope' => $scope->getId(),
                'product' => $product->getId(),
                'sourceProductVisibility' => null,
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                'source' => ProductVisibilityResolved::SOURCE_CATEGORY,
                'category' => $category->getId()
            ];
            self::assertContainsEquals($expected, $actual);
        }
    }

    private function assertInsertedFromBaseTable(array $actual): void
    {
        foreach ($this->getProductVisibilities() as $pv) {
            $visibility = $this->resolveVisibility($pv->getVisibility());

            if (null !== $visibility) {
                $expected = [
                    'scope' => $pv->getScope()->getId(),
                    'product' => $pv->getProduct()->getId(),
                    'sourceProductVisibility' => $pv->getId(),
                    'visibility' => $visibility,
                    'source' => ProductVisibilityResolved::SOURCE_STATIC,
                    'category' => null
                ];
                self::assertContainsEquals($expected, $actual);
            }
        }
    }

    /**
     * @return Product[]
     */
    private function getProducts(): array
    {
        return $this->getProductRepository()->findAll();
    }

    private function getProductRepository(): ProductEntityRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(Product::class);
    }

    private function getActualArray(): array
    {
        return $this->repository->createQueryBuilder('pvr')
            ->select(
                'IDENTITY(pvr.scope) as scope',
                'IDENTITY(pvr.product) as product',
                'IDENTITY(pvr.sourceProductVisibility) as sourceProductVisibility',
                'pvr.visibility as visibility',
                'pvr.source as source',
                'IDENTITY(pvr.category) as category'
            )
            ->getQuery()
            ->getResult();
    }

    private function getResolvedVisibilityManager(): EntityManager
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(ProductVisibilityResolved::class);
    }

    private function getCategoryRepository(): CategoryRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(Category::class);
    }

    /**
     * @return ProductVisibility[]
     */
    private function getProductVisibilities(): array
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(ProductVisibility::class)
            ->findAll();
    }

    public function testDeleteByProduct()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $category = $this->getCategoryByProduct($product);
        $this->repository->deleteByProduct($product);
        $this->repository->insertByProduct(
            $this->insertExecutor,
            $product,
            ProductVisibilityResolved::VISIBILITY_HIDDEN,
            $this->getScope(),
            $category
        );
        $this->repository->deleteByProduct($product);
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertEmpty($visibilities, 'Deleting has failed');
    }

    public function testInsertByProduct()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $category = $this->getCategoryByProduct($product);
        $this->repository->deleteByProduct($product);
        $this->repository->insertByProduct(
            $this->insertExecutor,
            $product,
            ProductVisibilityResolved::VISIBILITY_VISIBLE,
            $this->getScope(),
            $category
        );
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertCount(1, $visibilities, 'Not expected count of resolved visibilities');
    }

    public function testInsertByProductWithoutCategory()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);

        $visibility = new ProductVisibility();
        $visibility->setProduct($product);
        $visibility->setScope($this->getScope());
        $visibility->setVisibility(VisibilityInterface::HIDDEN);
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductVisibility::class);
        $em->persist($visibility);
        $em->flush();

        $this->repository->deleteByProduct($product);
        $this->repository->insertByProduct(
            $this->insertExecutor,
            $product,
            ProductVisibilityResolved::VISIBILITY_VISIBLE,
            $this->getScope(),
            null
        );
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertCount(1, $visibilities, 'Not expected count of resolved visibilities');
        /** @var ProductVisibilityResolved $actualVisibility */
        $actualVisibility = $visibilities[0];
        $this->assertEquals(BaseVisibilityResolved::VISIBILITY_HIDDEN, $actualVisibility->getVisibility());
    }

    private function getCategoryByProduct(Product $product): ?Category
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(Category::class)
            ->findOneByProduct($product);
    }

    private function getScope(): Scope
    {
        return $this->getContainer()->get('oro_scope.scope_manager')->findOrCreate(ProductVisibility::VISIBILITY_TYPE);
    }
}
