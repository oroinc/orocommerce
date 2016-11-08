<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\Common\Persistence\ObjectManager;
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
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\ResolvedEntityRepositoryTestTrait;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductRepositoryTest extends WebTestCase
{
    use ResolvedEntityRepositoryTestTrait ;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ProductRepository
     */
    protected $repository;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');

        $this->entityManager = $this->getResolvedVisibilityManager();
        $this->repository = $this->getContainer()
            ->get('oro_visibility.product_repository_holder')
            ->getRepository();

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

        $this->repository->insertStatic();
        $actual = $this->getActualArray();

        $this->assertCount(3, $actual);
        $this->assertInsertedFromBaseTable($actual);
    }

    public function testInsertByCategory()
    {
        $this->repository->clearTable();

        $qb = $this->getContainer()->get('doctrine')
            ->getManagerForClass(ProductVisibility::class)
            ->getRepository(ProductVisibility::class)
            ->createQueryBuilder('visibility');
        $qb->delete(ProductVisibility::class, 'visibillity')->getQuery()->execute();

        $scope = $this->getScope();
        $categoryScope = $this->scopeManager->findOrCreate('category_visibility', $scope);

        $this->repository->insertByCategory($scope, $categoryScope);

        $actual = $this->getActualArray();

        $this->assertCount(8, $actual);
        $this->assertInsertedByCategory($actual);
    }

    public function testInsertUpdateDeleteAndHasEntity()
    {
        $scope = $this->getScope();
        $categoryScope = $this->scopeManager->findOrCreate('category_visibility', $scope);
        $this->repository->clearTable();
        $this->repository->insertStatic();
        $this->repository->insertByCategory($scope, $categoryScope);

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

    /**
     * @param string $visibility
     * @return int|null
     */
    protected function resolveVisibility($visibility)
    {
        switch ($visibility) {
            case ProductVisibility::HIDDEN:
                return BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
                break;
            case ProductVisibility::VISIBLE:
                return BaseProductVisibilityResolved::VISIBILITY_VISIBLE;
                break;
            default:
                return null;
        }
    }

    /**
     * @param array $actual
     */
    protected function assertInsertedByCategory(array $actual)
    {
        $pv = $this->getProductVisibilities();
        $products = $this->getProducts();
        $scope = $this->getScope();
        foreach ($products as $product) {
            if (array_filter($pv, function (ProductVisibility $item) use ($product) {
                return $product === $item->getProduct();
            })) {
                continue;
            }

            $expected = [
                'scope' => $scope->getId(),
                'product' => $product->getId(),
                'sourceProductVisibility' => null,
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                'source' => ProductVisibilityResolved::SOURCE_CATEGORY,
                'category' => $this->getCategoryRepository()->findOneByProduct($product)->getId()
            ];
            $this->assertContains($expected, $actual);
        }
    }

    /**
     * @param array $actual
     */
    protected function assertInsertedFromBaseTable(array $actual)
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
                $this->assertContains($expected, $actual);
            }
        }
    }

    /**
     * @return Product[]
     */
    protected function getProducts()
    {
        return $this->getProductRepository()->findAll();
    }

    /**
     * @return ProductEntityRepository
     */
    protected function getProductRepository()
    {
        $className = $this->getContainer()->getParameter('oro_product.entity.product.class');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroProductBundle:Product');
    }

    /**
     * @return array
     */
    protected function getActualArray()
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

    /**
     * @return array
     */
    protected function getActual()
    {
        return $this->repository->findAll();
    }

    /**
     * @return ObjectManager
     */
    protected function getResolvedVisibilityManager()
    {
        $className = $this->getContainer()->getParameter('oro_visibility.entity.product_visibility_resolved.class');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className);
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        $className = $this->getContainer()->getParameter('oro_catalog.entity.category.class');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroCatalogBundle:Category');
    }

    /**
     * @return Category[]
     */
    protected function getCategories()
    {
        return $this->getCategoryRepository()->findAll();
    }

    /**
     * @return ProductVisibility[]
     */
    protected function getProductVisibilities()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroVisibilityBundle:Visibility\ProductVisibility')
            ->findAll();
    }

    /**
     * @return InsertFromSelectQueryExecutor
     */
    protected function getInsertFromSelectExecutor()
    {
        return $this->getContainer()
            ->get('oro_entity.orm.insert_from_select_query_executor');
    }

    public function testDeleteByProduct()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var $product Product */
        $category = $this->getCategoryByProduct($product);
        $this->repository->deleteByProduct($product);
        $this->repository->insertByProduct(
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
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var $product Product */
        $category = $this->getCategoryByProduct($product);
        $this->repository->deleteByProduct($product);
        $this->repository->insertByProduct(
            $product,
            ProductVisibilityResolved::VISIBILITY_VISIBLE,
            $this->getScope(),
            $category
        );
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertCount(1, $visibilities, 'Not expected count of resolved visibilities');
    }

    /**
     * @param Product $product
     * @return null|\Oro\Bundle\CatalogBundle\Entity\Category
     */
    protected function getCategoryByProduct(Product $product)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')
            ->findOneByProduct($product);
    }

    /**
     * @return Scope
     */
    protected function getScope()
    {
        return $this->getContainer()->get('oro_scope.scope_manager')->findOrCreate(ProductVisibility::VISIBILITY_TYPE);
    }
}
