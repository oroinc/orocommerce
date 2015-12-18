<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AccountProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /** @var  Registry */
    protected $registry;
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var AccountProductRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->registry = $this->getContainer()->get('doctrine');
        $this->entityManager = $this->registry->getManager();
        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
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
        $this->assertCount(4, $this->getRepository()->findAll());
        $deletedCount = $this->getRepository()->clearTable();

        $this->assertCount(0, $this->getRepository()->findAll());
        $this->assertEquals(4, $deletedCount);
    }

    public function testInsertByCategory()
    {
        $accountProductVisibility = $this->registry
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->findOneBy(['visibility' => AccountProductVisibility::CATEGORY]);
        $this->getRepository()->clearTable();
        $visibilityValue = BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
        $this->getRepository()->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            $visibilityValue,
            $this->registry->getRepository('OroB2BCatalogBundle:Category')->findAll(),
            $accountProductVisibility->getAccount()->getId()
        );
        $resolved = $this->getResolvedValues();
        $this->assertCount(1, $resolved);
        $resolvedValue = $resolved[0];
        $this->assertEquals($resolvedValue->getAccount(), $accountProductVisibility->getAccount());
        $this->assertEquals(
            $resolvedValue->getCategory()->getId(),
            $this->getReference('category_1_5_6_7')->getId()
        );
        $this->assertEquals($resolvedValue->getWebsite(), $accountProductVisibility->getWebsite());
        $this->assertEquals($resolvedValue->getProduct(), $accountProductVisibility->getProduct());
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
        $this->assertCount(2, $resolved);
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
     * @return AccountProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }

    public function testFindByPrimaryKey()
    {
        /** @var AccountProductVisibilityResolved $actualEntity */
        $actualEntity = $this->repository->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $expectedEntity = $this->repository->findByPrimaryKey(
            $actualEntity->getAccount(),
            $actualEntity->getProduct(),
            $actualEntity->getWebsite()
        );

        $this->assertEquals(spl_object_hash($expectedEntity), spl_object_hash($actualEntity));
    }

    public function testUpdateCurrentProductRelatedEntities()
    {
        $website = $this->getDefaultWebsite();
        /** @var Product $product */
        $product = $this->getReference('product.5');
        /** @var Account $account */
        $account = $this->getReference('account.level_1');

        $resolvedVisibility = $this->repository->findByPrimaryKey($account, $product, $website);
        $this->assertNotNull($resolvedVisibility);
        $this->assertEquals(BaseProductVisibilityResolved::VISIBILITY_HIDDEN, $resolvedVisibility->getVisibility());

        $this->repository
            ->updateCurrentProductRelatedEntities($website, $product, BaseProductVisibilityResolved::VISIBILITY_HIDDEN);

        $this->entityManager->refresh($resolvedVisibility);
        $this->assertEquals(BaseProductVisibilityResolved::VISIBILITY_HIDDEN, $resolvedVisibility->getVisibility());
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(['name' => 'Default']);
    }
}
