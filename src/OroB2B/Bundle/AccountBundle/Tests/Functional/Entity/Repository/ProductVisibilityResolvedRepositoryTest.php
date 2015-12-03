<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

/**
 * @dbIsolation
 */
class ProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /**
     * @var ProductVisibilityResolvedRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityResolvedData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData'
            ]
        );
    }

    public function testClearTable()
    {
        $deletedCount = $this->repository->clearTable();
        $actual = $this->repository->findAll();

        $this->assertSame(0, count($actual));
        $this->assertSame(4, $deletedCount);
    }

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

        $actual = $this->repository->findAll();
        $i = 0;
        $this->assertSame(15, count($actual));
        foreach ($this->getWebsites() as $website) {
            $j = 0;
            foreach ($categories as $category) {
                $expected = [
                    'websiteId' => $website->getId(),
                    'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'visibility' => BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                    'categoryId' => $category->getId(),
                    'productId' => $category->getProducts()[0]->getId()
                ];
                $index = $i * count($categories) + $j;
                $this->assertProductVisibilityResolved($actual[$index], $expected);
                $j++;
            }
            $i++;
        }
    }

    public function testDeleteByVisibility()
    {
        $this->repository->deleteByVisibility(ProductVisibility::CONFIG);
        $actual = $this->repository->findAll();
        $this->assertSame(14, count($actual));
    }

    public function testUpdateFromBaseTable()
    {
        $updatedHidden = $this->repository->updateFromBaseTable(
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            ProductVisibility::HIDDEN
        );
        $updatedVisible = $this->repository->updateFromBaseTable(
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            ProductVisibility::VISIBLE
        );
        $this->assertSame(1, $updatedHidden);
        $this->assertSame(3, $updatedVisible);
    }

    /**
     * @param BaseProductVisibilityResolved $visibility
     * @param array $expected
     */
    protected function assertProductVisibilityResolved(BaseProductVisibilityResolved $visibility, array $expected)
    {
        $this->assertSame($expected['websiteId'], $visibility->getWebsite()->getId());
        $this->assertSame($expected['source'], $visibility->getSource());
        $this->assertSame($expected['visibility'], $visibility->getVisibility());
        $this->assertSame($expected['categoryId'], $visibility->getCategoryId());
        $this->assertSame($expected['productId'], $visibility->getProduct()->getId());
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
