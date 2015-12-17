<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /** @var  Registry */
    protected $registry;

    /** @var AccountGroupProductRepository */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->registry = $this->getContainer()->get('doctrine');

        $this->repository = $this->registry
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
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
        $this->assertCount(6, $this->getRepository()->findAll());
        $deletedCount = $this->getRepository()->clearTable();

        $this->assertCount(0, $this->getRepository()->findAll());
        $this->assertEquals(6, $deletedCount);
    }

    public function testInsertByCategory()
    {
        $accountGroupProductVisibility = $this->registry
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findOneBy(['visibility' => AccountGroupProductVisibility::CATEGORY]);
        $groupId = $accountGroupProductVisibility->getAccountGroup()->getId();
        $this->getRepository()->clearTable();
        $visibilityValue = BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
        $this->getRepository()->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            $visibilityValue,
            $this->registry->getRepository('OroB2BCatalogBundle:Category')->findAll(),
            $groupId
        );
        $resolved = $this->getResolvedValues();
        $this->assertCount(2, $resolved);
        $resolvedValue = $resolved[1];
        $this->assertEquals($resolvedValue->getAccountGroup()->getId(), $groupId);
        $expectedCategoryId = $this->registry
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByProduct($accountGroupProductVisibility->getProduct())
            ->getId();
        $this->assertEquals($expectedCategoryId, $resolvedValue->getCategory()->getId());
        $this->assertEquals(
            spl_object_hash($resolvedValue->getWebsite()),
            spl_object_hash($accountGroupProductVisibility->getWebsite())
        );
        $this->assertEquals(
            spl_object_hash($resolvedValue->getProduct()),
            spl_object_hash($accountGroupProductVisibility->getProduct())
        );
        $this->assertEquals($resolvedValue->getVisibility(), $visibilityValue);
    }

    public function testInsertStatic()
    {
        $this->getRepository()->clearTable();
        $this->getRepository()->insertStatic($this->getInsertFromSelectExecutor());
        $resolved = $this->getResolvedValues();
        $this->assertCount(4, $resolved);
        $visibilities = $this->registry
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findAll();
        foreach ($resolved as $resolvedValue) {
            $source = $this->getVisibility(
                $visibilities,
                $resolvedValue->getProduct(),
                $resolvedValue->getAccountGroup(),
                $resolvedValue->getWebsite()
            );
            $this->assertNotNull($source);
            if ($resolvedValue->getVisibility() == BaseProductVisibilityResolved::VISIBILITY_HIDDEN) {
                $visibility = AccountGroupProductVisibility::HIDDEN;
            } else {
                $visibility = AccountGroupProductVisibility::VISIBLE;
            }
            $this->assertEquals(
                $source->getVisibility(),
                $visibility
            );
        }
    }

    public function testFindByPrimaryKey()
    {
        /** @var AccountGroupProductVisibilityResolved $actualEntity */
        $actualEntity = $this->repository->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $expectedEntity = $this->repository->findByPrimaryKey(
            $actualEntity->getAccountGroup(),
            $actualEntity->getProduct(),
            $actualEntity->getWebsite()
        );

        $this->assertEquals(spl_object_hash($expectedEntity), spl_object_hash($actualEntity));
    }

    /**
     * @return AccountGroupProductVisibilityResolved[]
     */
    protected function getResolvedValues()
    {
        return $this->registry
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->findAll();
    }

    /**
     * @param AccountGroupProductVisibility[] $visibilities
     * @param Product $product
     * @param AccountGroup $accountGroup
     * @param Website $website
     *
     * @return AccountGroupProductVisibility|null
     */
    protected function getVisibility(
        $visibilities,
        Product $product,
        AccountGroup $accountGroup,
        Website $website
    ) {
        foreach ($visibilities as $visibility) {
            if (spl_object_hash($product) == spl_object_hash($visibility->getProduct())
                && spl_object_hash($accountGroup) == spl_object_hash($visibility->getAccountGroup())
                && spl_object_hash($website) == spl_object_hash($visibility->getWebsite())
            ) {
                return $visibility;
            }
        }

        return null;
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
            'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility'
        );
    }

    /**
     * @return AccountGroupProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
    }
}
