<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\CustomerBundle\Tests\Functional\Entity\Repository\ResolvedEntityRepositoryTestTrait;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository as ProductEntityRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

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
     * @var Website
     */
    protected $website;

    protected function setUp()
    {
        $this->initClient();

        $this->website = $this->getWebsiteRepository()->getDefaultWebsite();

        $this->entityManager = $this->getResolvedVisibilityManager();
        $this->repository = $this->entityManager
            ->getRepository(ProductVisibilityResolved::class);

        $this->loadFixtures([LoadProductVisibilityData::class]);
    }

    public function testClearTableByWebsite()
    {
        $defaultWebsiteProductVisibilitiesCount = count($this->repository->findBy(['website' => $this->website]));

        $deleted = $this->repository->clearTable($this->website);
        $actual = $this->repository->findBy(['website' => $this->website]);

        $this->assertEmpty($actual);
        $this->assertSame($defaultWebsiteProductVisibilitiesCount, $deleted);
    }

    public function testFindByPrimaryKey()
    {
        /** @var ProductVisibilityResolved $actualEntity */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $entity = new ProductVisibilityResolved($website, $product);
        $this->getResolvedVisibilityManager()->persist($entity);
        $this->getResolvedVisibilityManager()->flush();

        $actualEntity = $this->repository->findOneBy([]);
        $expectedEntity = $this->repository->findByPrimaryKey(
            $actualEntity->getProduct(),
            $actualEntity->getWebsite()
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

        $this->repository->insertStatic($this->getInsertFromSelectExecutor());
        $actual = $this->getActualArray();

        $this->assertCount(3, $actual);
        $this->assertInsertedFromBaseTable($actual);
    }

    public function testInsertByCategory()
    {
        $this->repository->clearTable();

        $this->repository->insertByCategory($this->getInsertFromSelectExecutor());

        $actual = $this->getActualArray();

        $this->assertCount(24, $actual);
        $this->assertInsertedByCategory($actual);
    }

    public function testInsertFromBaseTableByWebsite()
    {
        $this->repository->clearTable();

        $this->repository->insertStatic($this->getInsertFromSelectExecutor(), $this->website);
        $actual = $this->getActualArray();

        $this->assertCount(3, $actual);
        $this->assertInsertedFromBaseTable($actual);
    }

    public function testInsertByCategoryForWebsite()
    {
        $this->repository->clearTable();

        $this->repository->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            $this->website
        );

        $actual = $this->getActualArray();
        $this->assertCount(0, $actual);
    }

    public function testInsertUpdateDeleteAndHasEntity()
    {
        $this->repository->clearTable();
        $this->repository->insertStatic($this->getInsertFromSelectExecutor());
        $this->repository->insertByCategory($this->getInsertFromSelectExecutor());

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);

        $where = ['product' => $product, 'website' => $website];
        $this->assertTrue($this->repository->hasEntity($where));

        $this->assertDelete($this->repository, $where);
        $this->assertInsert(
            $this->entityManager,
            $this->repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            BaseProductVisibilityResolved::SOURCE_STATIC
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
     * @param Website $website
     */
    protected function assertInsertedByCategory(array $actual, Website $website = null)
    {
        $pv = $this->getProductVisibilities();
        $products = $this->getProducts();
        $websites = $website ? [$website] : $this->getWebsiteRepository()->getAllWebsites();

        foreach ($products as $product) {
            foreach ($websites as $website) {
                if (array_filter($pv, function (ProductVisibility $item) use ($website, $product) {
                    return $website === $item->getWebsite() && $product === $item->getProduct();
                })) {
                    continue;
                }

                $expected = [
                    'website' => $website->getId(),
                    'product' => $product->getId(),
                    'sourceProductVisibility' => null,
                    'visibility' => BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                    'source' => ProductVisibilityResolved::SOURCE_CATEGORY,
                    'category' => $this->getCategoryRepository()->findOneByProduct($product)->getId()
                ];
                $this->assertContains($expected, $actual);
            }
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
                    'website' => $pv->getWebsite()->getId(),
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
                'IDENTITY(pvr.website) as website',
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
        $className = $this->getContainer()->getParameter('oro_account.entity.product_visibility_resolved.class');

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
            ->getRepository('OroCustomerBundle:Visibility\ProductVisibility')
            ->findAll();
    }

    /**
     * @return WebsiteRepository
     */
    protected function getWebsiteRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass(Website::class)
            ->getRepository(Website::class);
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
            $this->getInsertFromSelectExecutor(),
            $product,
            ProductVisibilityResolved::VISIBILITY_HIDDEN,
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
            $this->getInsertFromSelectExecutor(),
            $product,
            ProductVisibilityResolved::VISIBILITY_VISIBLE,
            $category
        );
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertCount(3, $visibilities, 'Not expected count of resolved visibilities');
        $resolvedVisibility = $this->repository->findOneBy(
            [
                'product' => $product,
                'website' => $this->getWebsiteRepository()->getDefaultWebsite()
            ]
        );
        /** @var $resolvedVisibility ProductVisibility  */
        $this->assertNull($resolvedVisibility, 'Not expected of resolved visibilities');

        $product = $this->getReference(LoadProductData::PRODUCT_4);
        $category = $this->getCategoryByProduct($product);
        $this->repository->deleteByProduct($product);
        $this->repository->insertByProduct(
            $this->getInsertFromSelectExecutor(),
            $product,
            ProductVisibilityResolved::VISIBILITY_HIDDEN,
            $category
        );
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertCount(4, $visibilities, 'Not expected count of resolved visibilities');
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
}
