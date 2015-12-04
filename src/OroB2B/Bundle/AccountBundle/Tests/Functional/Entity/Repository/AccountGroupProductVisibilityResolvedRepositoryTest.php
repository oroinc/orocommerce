<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /** @var  Registry */
    protected $registry;

    protected function setUp()
    {
        $this->initClient();
        $this->registry = $this->getContainer()->get('doctrine');

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityResolvedData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
            ]
        );
    }

    public function testClearTable()
    {
        $deletedCount = $this->getRepository()->clearTable();
        $actual = $this->getRepository()->findAll();

        $this->assertSame(0, count($actual));
        $this->assertSame(4, $deletedCount);
    }

    /**
     * @depends testClearTable
     */
    public function testInsertByCategory()
    {
        $categories = $this->getCategories();
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference(LoadGroups::GROUP1);
        $this->assertCount(0, $this->getRepository()->findAll());
        $this->getRepository()->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            array_map(
                function ($category) {
                    /** @var \OroB2B\Bundle\CatalogBundle\Entity\Category $category */
                    return $category->getId();
                },
                $categories
            ),
            $accountGroup->getId()
        );
        /** @var AccountGroupProductVisibilityResolved[] $actual */
        $actual = $this->getRepository()->findAll();
        $websites = $this->registry->getRepository('OroB2BWebsiteBundle:Website')->findAll();
        foreach ($websites as $website) {
            foreach ($categories as $category) {
                foreach ($actual as $key => $visibility) {
                    if ($visibility->getWebsite()->getId() == $website->getId()
                        && $visibility->getCategoryId() == $category->getId()
                    ) {
                        $this->assertEquals(BaseProductVisibilityResolved::SOURCE_CATEGORY, $visibility->getSource());
                        $this->assertEquals(
                            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
                            $visibility->getVisibility()
                        );
                        $this->assertEquals($visibility->getProduct()->getId(), $category->getProducts()[0]->getId());
                        unset($actual[$key]);
                        break;
                    }
                }
            }
        }
        $this->assertCount(0, $actual);
    }

    public function testDeleteByVisibility()
    {
        $this->assertCount(12, $this->getRepository()->findAll());
        /** @var AccountGroupProductVisibilityResolved $visibility */
        $visibility = $this->getSourceRepository()->findOneBy([]);
        $product = $visibility->getProduct();
        $accountGroup = $visibility->getAccountGroup();
        $website = $visibility->getWebsite();
        $this->assertCount(
            1,
            $this->getRepository()->findBy(
                ['website' => $website, 'accountGroup' => $accountGroup, 'product' => $product]
            )
        );
        $visibility->setVisibility(AccountGroupProductVisibility::CURRENT_PRODUCT);
        $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->flush();
        $this->getRepository()->deleteByVisibility(AccountGroupProductVisibility::CURRENT_PRODUCT);
        $actual = $this->getRepository()->findAll();
        $this->assertCount(
            0,
            $this->getRepository()->findBy(
                ['website' => $website, 'accountGroup' => $accountGroup, 'product' => $product]
            )
        );
        $this->assertCount(11, $actual);
    }


    public function testUpdateFromBaseTable()
    {
        $updatedHidden = $this->getRepository()->updateFromBaseTable(
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            ProductVisibility::HIDDEN
        );
        $updatedVisible = $this->getRepository()->updateFromBaseTable(
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            ProductVisibility::VISIBLE
        );
        $this->assertSame(1, $updatedHidden);
        $this->assertSame(1, $updatedVisible);
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

    /**
     * @return EntityRepository
     */
    protected function getSourceRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility'
        );
    }

    /**
     * @return AccountGroupProductVisibilityResolvedRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
    }
}
