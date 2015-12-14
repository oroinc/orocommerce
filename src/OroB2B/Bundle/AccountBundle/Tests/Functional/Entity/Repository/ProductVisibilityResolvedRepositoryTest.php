<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\Query;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class ProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /**
     * @var ProductVisibilityResolvedRepository
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

        $this->repository = $this->getPVRManager()
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    public function testClearTable()
    {
        $this->repository->clearTable();
        $actual = $this->repository->findAll();

        $this->assertSame(0, count($actual));
    }

    /**
     * @depends testClearTable
     */
    public function testInsertFromBaseTable()
    {
        $this->repository->insertFromBaseTable($this->getInsertFromSelectExecutor());
        $actual = $this->getActualArray();

        $this->assertCount(4, $actual);
        $this->assertInsertedFromBaseTable($actual);
    }

    /**
     * @depends testInsertFromBaseTable
     */
    public function testInsertByCategory()
    {
        $this->repository->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            array_map(function ($category) {
                /** @var \OroB2B\Bundle\CatalogBundle\Entity\Category $category */
                return $category->getId();
            }, $this->getCategories())
        );

        $actual = $this->getActualArray();

        $this->assertCount(18, $actual);
        $this->assertInsertedByCategory($actual);
    }

    public function testClearTableByWebsite()
    {
        $deleted = $this->repository->clearTable($this->website);
        $actual = $this->repository->findBy(['website' => $this->website]);

        $this->assertCount(0, $actual);
        $this->assertSame(6, $deleted);
    }

    /**
     * @depends testClearTableByWebsite
     */
    public function testInsertFromBaseTableByWebsite()
    {
        $this->repository->insertFromBaseTable($this->getInsertFromSelectExecutor(), $this->website);
        $actual = $this->getActualArray();

        $this->assertCount(16, $actual);
        $this->assertInsertedFromBaseTable($actual);
    }

    /**
     * @depends testInsertFromBaseTableByWebsite
     */
    public function testInsertByCategoryForWebsite()
    {
        $categories = $this->getCategories();

        $this->repository->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            array_map(function ($category) {
                /** @var \OroB2B\Bundle\CatalogBundle\Entity\Category $category */
                return $category->getId();
            }, $categories),
            $this->website
        );

        $actual = $this->getActualArray();
        $this->assertCount(18, $actual);
        $this->assertInsertedByCategory($actual, $this->website);
    }

    /**
     * @param string $visibility
     * @return int
     */
    protected function resolveVisibility($visibility)
    {
        switch ($visibility) {
            case ProductVisibility::HIDDEN:
                $visibility = BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
                break;
            case ProductVisibility::VISIBLE:
                $visibility = BaseProductVisibilityResolved::VISIBILITY_VISIBLE;
                break;
            default:
                $visibility = null;
        }

        return $visibility;
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
                    'visibility' => BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
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
     * @return array|\OroB2B\Bundle\ProductBundle\Entity\Product[]
     */
    protected function getProducts()
    {
        $repository = $this->getProductRepository();

        return $repository->findAll();
    }

    /**
     * @return \OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository
     */
    protected function getProductRepository()
    {
        $className = $this->getContainer()->getParameter('orob2b_product.product.class');

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
                'pvr.categoryId as category'
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
     * @return \Doctrine\Common\Persistence\ObjectManager|null|object
     */
    protected function getPVRManager()
    {
        $className = $this->getContainer()->getParameter('orob2b_account.entity.product_visibility_resolved.class');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className);
    }

    /**
     * @return \OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository
     */
    protected function getCategoryRepository()
    {
        $className = $this->getContainer()->getParameter('orob2b_catalog.category.class');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroB2BCatalogBundle:Category');
    }

    /**
     * @return array|\OroB2B\Bundle\CatalogBundle\Entity\Category[]
     */
    protected function getCategories()
    {
        return $this->getCategoryRepository()->findAll();
    }

    /**
     * @return array|\OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility[]
     */
    protected function getProductVisibilities()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findAll();
    }

    /**
     * @return array|\OroB2B\Bundle\WebsiteBundle\Entity\Website[]
     */
    protected function getWebsites()
    {
        $className = $this->getContainer()->getParameter('orob2b_website.website.class');
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroB2BWebsiteBundle:Website');

        return $repository->findAll();
    }

    /**
     * @return \Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor
     */
    protected function getInsertFromSelectExecutor()
    {
        return $this->getContainer()
            ->get('oro_entity.orm.insert_from_select_query_executor');
    }
}
