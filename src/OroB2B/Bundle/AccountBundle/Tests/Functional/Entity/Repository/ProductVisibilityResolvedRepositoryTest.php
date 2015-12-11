<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
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

        $this->repository = $this->getContainer()->get('doctrine')
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
    }

    /**
     * @depends testInsertFromBaseTable
     */
    public function testInsertByCategory()
    {
        $categories = $this->getCategories();

        $this->repository->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            array_map(function ($category) {
                /** @var \OroB2B\Bundle\CatalogBundle\Entity\Category $category */
                return $category->getId();
            }, $categories)
        );

        $actual = $this->getActualArray();
        $this->assertCount(12, $actual);
    }

    public function testClearTableByWebsite()
    {
        $deleted = $this->repository->clearTable($this->website);
        $actual = $this->repository->findBy(['website' => $this->website]);

        $this->assertSame(0, count($actual));
        $this->assertSame(4, $deleted);
    }

    /**
     * @depends testClearTableByWebsite
     */
    public function testInsertFromBaseTableByWebsite()
    {
        $this->repository->insertFromBaseTable($this->getInsertFromSelectExecutor(), $this->website);
        $actual = $this->getActualArray();

        $this->assertCount(12, $actual);
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
        $this->assertCount(12, $actual);
    }

    /**
     * @return array
     */
    protected function getActualArray()
    {
        return $this->repository->createQueryBuilder('pvr')
            ->select('pvr')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);
    }

    /**
     * @return array|\OroB2B\Bundle\CatalogBundle\Entity\Category[]
     */
    protected function getCategories()
    {
        $className = $this->getContainer()->getParameter('orob2b_catalog.category.class');
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('c')
            ->select('c', 'p')
            ->leftJoin('c.products', 'p')
            ->getQuery()
            ->execute();
    }

    /**
     * @return array|\OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility[]
     */
    protected function getProductVisibilities()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
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
