<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository\ResolvedEntityRepositoryTestTrait;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository as ProductEntityRepository;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

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

        $this->website = $this->getWebsites()[0];
        $this->entityManager = $this->getResolvedVisibilityManager();
        $this->repository = $this->entityManager
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');

        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']);
    }

    public function testClearTableByWebsite()
    {
        $deleted = $this->repository->clearTable($this->website);
        $actual = $this->repository->findBy(['website' => $this->website]);

        $this->assertEmpty($actual);
        $this->assertSame(6, $deleted);
    }

    public function testFindByPrimaryKey()
    {
        /** @var ProductVisibilityResolved $actualEntity */
        $actualEntity = $this->repository->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

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
        $websites = $website ? [$website] : $this->getWebsites();

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
        $className = $this->getContainer()->getParameter('orob2b_product.entity.product.class');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroB2BProductBundle:Product');
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
        $className = $this->getContainer()->getParameter('orob2b_account.entity.product_visibility_resolved.class');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className);
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        $className = $this->getContainer()->getParameter('orob2b_catalog.entity.category.class');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroB2BCatalogBundle:Category');
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
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findAll();
    }

    /**
     * @return Website[]
     */
    protected function getWebsites()
    {
        $className = $this->getContainer()->getParameter('orob2b_website.entity.website.class');
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroB2BWebsiteBundle:Website');

        return $repository->findAll();
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
                'website' => $this->getDefaultWebsite()
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
     * @return \OroB2B\Bundle\WebsiteBundle\Entity\Website
     */
    protected function getDefaultWebsite()
    {
        $className = $this->getContainer()->getParameter('orob2b_website.entity.website.class');
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroB2BWebsiteBundle:Website');

        return $repository->getDefaultWebsite();
    }

    /**
     * @param Product $product
     * @return null|\OroB2B\Bundle\CatalogBundle\Entity\Category
     */
    protected function getCategoryByProduct(Product $product)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByProduct($product);
    }
}
