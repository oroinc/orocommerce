<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AccountProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /** @var  Registry */
    protected $registry;

    protected function setUp()
    {
        $this->initClient();
        $this->registry = $this->getContainer()->get('doctrine');

        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
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

    protected function tearDown()
    {
        $this->getContainer()->get('doctrine')->getManager()->clear();
        parent::tearDown();
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
     * @depends testUpdateFromBaseTable
     */
    public function testUpdateFromBaseTableForCurrentProduct()
    {
        $resolvedVisibility = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findOneBy([]);

        $website = $resolvedVisibility->getWebsite();
        $product = $resolvedVisibility->getProduct();

        /** @var AccountProductVisibilityResolved $accountProductVisibilityResolved */
        $accountProductVisibilityResolved = $this->getRepository()
            ->findOneBy(
                [
                    'website' => $website,
                    'product' => $product,
                    'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                ]
            );

        /** @var AccountProductVisibility $accountProductVisibility */
        $accountProductVisibility = $this->getSourceRepository()
            ->findOneBy(
                [
                    'website' => $website,
                    'product' => $product,
                    'account' => $accountProductVisibilityResolved->getAccount(),
                ]
            );
        $accountProductVisibility->setVisibility(AccountProductVisibility::CURRENT_PRODUCT);
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
        $em->flush();

        $this->getRepository()->updateFromBaseTableForCurrentProduct(
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );
        $em->flush();
        $em->clear();
        $accountProductVisibilityResolved = $this->getRepository()
            ->findOneBy(
                [
                    'website' => $website,
                    'product' => $product,
                    'account' => $accountProductVisibilityResolved->getAccount(),
                ]
            );
        $this->assertEquals(
            $accountProductVisibilityResolved->getSource(),
            BaseProductVisibilityResolved::SOURCE_STATIC
        );
        $this->assertNull($accountProductVisibilityResolved->getCategoryId());
        $this->assertEquals(
            $accountProductVisibilityResolved->getVisibility(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );
    }

    public function testClearTable()
    {
        $deletedCount = $this->getRepository()->clearTable();
        $actual = $this->getRepository()->findAll();

        $this->assertSame(0, count($actual));
        $this->assertSame(3, $deletedCount);
    }

    /**
     * @depends testClearTable
     */
    public function testInsertByCategory()
    {
        $categories = $this->getCategories();
        /** @var Account $account */
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        $this->assertCount(0, $this->getRepository()->findAll());
        $this->getRepository()->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            array_map(
                function ($category) {
                    /** @var Category $category */
                    return $category->getId();
                },
                $categories
            ),
            $account->getId()
        );
        /** @var AccountProductVisibilityResolved[] $actual */
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

    /**
     * @depends testUpdateFromBaseTableForCurrentProduct
     */
    public function testDeleteByVisibility()
    {
        $visibility = $this->getSourceRepository()->findOneBy(
            ['visibility' => AccountProductVisibility::CURRENT_PRODUCT]
        );
        /** @var AccountProductVisibilityResolved $visibility */
        $product = $visibility->getProduct();
        $account = $visibility->getAccount();
        $website = $visibility->getWebsite();

        $visibilityResolved = new AccountProductVisibilityResolved($website, $product, $account);
        $visibilityResolved->setVisibility(1);
        $visibilityResolved->setSource(3);

        $em = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $em->persist($visibilityResolved);
        $em->flush();
        $this->assertCount(13, $this->getRepository()->findAll());
        $this->getRepository()->deleteByVisibility(AccountProductVisibility::CURRENT_PRODUCT);
        $this->assertCount(
            0,
            $this->getRepository()->findBy(
                ['website' => $website, 'account' => $account, 'product' => $product]
            )
        );
        $this->assertCount(12, $this->getRepository()->findAll());
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
     * @return Category[]
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
     * @return Website[]
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
     * @return InsertFromSelectQueryExecutor
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
            'OroB2BAccountBundle:Visibility\AccountProductVisibility'
        );
    }

    /**
     * @return AccountProductVisibilityResolvedRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }
}
