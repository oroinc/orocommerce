<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
            ]
        );
    }

    protected function tearDown()
    {
        $this->registry->getManager()->clear();
        parent::tearDown();
    }

    public function testClearTable()
    {
        $this->assertCount(3, $this->getRepository()->findAll());
        $deletedCount = $this->getRepository()->clearTable();

        $this->assertCount(0, $this->getRepository()->findAll());
        $this->assertEquals(3, $deletedCount);
    }

    public function testInsertByCategory()
    {
        /** @var Account $account */
        $account = $this->getReference('account.level_1');
        $apv = $this->registry->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->findOneBy(['product' => $this->getReference(LoadProductData::PRODUCT_1), 'account' => $account]);
        $apv->setVisibility(AccountProductVisibility::CATEGORY);
        $this->registry->getManager()->flush();
        $this->getRepository()->clearTable();
        $visibilityValue = BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
        $this->getRepository()->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            $visibilityValue,
            $this->registry->getRepository('OroB2BCatalogBundle:Category')->findAll(),
            $account->getId()
        );
        $resolved = $this->getResolvedValues();
        $this->assertCount(1, $resolved);
        $resolvedValue = $resolved[0];
        $this->assertEquals($resolvedValue->getAccount(), $account);
        $this->assertEquals(
            $resolvedValue->getCategoryId(),
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId()
        );
        $this->assertEquals($resolvedValue->getWebsite(), $apv->getWebsite());
        $this->assertEquals($resolvedValue->getProduct(), $apv->getProduct());
        $this->assertEquals($resolvedValue->getVisibility(), $visibilityValue);
    }

    /**
     * @return AccountProductVisibilityResolved[]
     */
    protected function getResolvedValues()
    {
        return $this->registry
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->findAll();
    }

    public function testInsertStatic()
    {
        $this->getRepository()->clearTable();
        $this->getRepository()->insertStatic($this->getInsertFromSelectExecutor());
        $resolved = $this->getResolvedValues();
        $this->assertCount(1, $resolved);
        foreach ($resolved as $resolvedValue) {
            $source = $this->registry
                ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
                ->findOneBy(
                    [
                        'product' => $resolvedValue->getProduct(),
                        'account' => $resolvedValue->getAccount(),
                        'website' => $resolvedValue->getWebsite(),
                    ]
                );
            $this->assertNotNull($source);
            if ($resolvedValue->getVisibility() == BaseProductVisibilityResolved::VISIBILITY_HIDDEN) {
                $visibility = AccountProductVisibility::HIDDEN;
            } else {
                $visibility = AccountProductVisibility::VISIBLE;
            }
            $this->assertEquals(
                $source->getVisibility(),
                $visibility
            );
        }
    }

    public function testInsertForCurrentProductFallback()
    {
        /** @var AccountProductVisibility $accountProductVisibility */
        $accountProductVisibility = $this->getSourceRepository()
            ->findOneBy(['visibility' => AccountProductVisibility::CURRENT_PRODUCT]);

        $productVisibilityResolved = $this->getProductVisibilityResolved($accountProductVisibility);
        $this->assertEquals(
            $productVisibilityResolved->getVisibility(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );
        $this->getRepository()->insertForCurrentProductFallback(
            $this->getInsertFromSelectExecutor(),
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN
        );

        // Ignore Config value, take from productVisibilityResolved VISIBLE value
        $this->assertEquals(
            $this->getResolvedVisibilityBySource($accountProductVisibility),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );
        $productVisibilityResolved->setVisibility(BaseProductVisibilityResolved::VISIBILITY_HIDDEN);

        $this->getRepository()->clearTable();
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();


        $this->getRepository()->insertForCurrentProductFallback(
            $this->getInsertFromSelectExecutor(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );

        // Ignore Config value, take from productVisibilityResolved HIDDEN value
        $this->assertEquals(
            $this->getResolvedVisibilityBySource($accountProductVisibility),
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN
        );

        $this->registry->getManager()->remove($this->getProductVisibilityResolved($accountProductVisibility));
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();
        $this->getRepository()->clearTable();

        $this->getRepository()->insertForCurrentProductFallback(
            $this->getInsertFromSelectExecutor(),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );

        // Take VISIBLE value from Config, because productVisibilityResolved for this product and website is absent
        $this->assertEquals(
            $this->getResolvedVisibilityBySource($accountProductVisibility),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );

        $this->getRepository()->clearTable();


        $this->getRepository()->insertForCurrentProductFallback(
            $this->getInsertFromSelectExecutor(),
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN
        );

        // Take HIDDEN value from Config, because productVisibilityResolved for this product and website is absent
        $this->assertEquals(
            $this->getResolvedVisibilityBySource($accountProductVisibility),
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE
        );
    }

    /**
     * @param AccountProductVisibility $accountProductVisibility
     * @return null|integer
     */
    protected function getResolvedVisibilityBySource(AccountProductVisibility $accountProductVisibility)
    {
        /** @var AccountProductVisibilityResolved $visibility */
        $visibility = $this->getRepository()->findOneBy(
            [
                'product' => $accountProductVisibility->getProduct(),
                'account' => $accountProductVisibility->getAccount(),
                'website' => $accountProductVisibility->getWebsite(),
            ]
        );

        return $visibility ? $visibility->getVisibility() : null;
    }

    /**
     * @param AccountProductVisibility $accountProductVisibility
     * @return ProductVisibilityResolved
     */
    public function getProductVisibilityResolved(AccountProductVisibility $accountProductVisibility)
    {
        return $this->registry
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findOneBy(
                [
                    'product' => $accountProductVisibility->getProduct(),
                    'website' => $accountProductVisibility->getWebsite(),
                ]
            );
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
