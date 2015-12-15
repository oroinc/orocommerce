<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
        $this->assertCount(4, $this->getRepository()->findAll());
        $deletedCount = $this->getRepository()->clearTable();

        $this->assertCount(0, $this->getRepository()->findAll());
        $this->assertEquals(4, $deletedCount);
    }

    public function testInsertByCategory()
    {
        /** @var AccountGroup $group */
        $group = $this->getReference(LoadGroups::GROUP1);
        $agpv = $this->registry->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findOneBy(['product' => $this->getReference(LoadProductData::PRODUCT_1), 'accountGroup' => $group]);
        $agpv->setVisibility(AccountGroupProductVisibility::CATEGORY);
        $this->registry->getManager()->flush();
        $this->getRepository()->clearTable();
        $visibilityValue = BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
        $this->getRepository()->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            $visibilityValue,
            $this->registry->getRepository('OroB2BCatalogBundle:Category')->findAll(),
            $group->getId()
        );
        $resolved = $this->getResolvedValues();
        $this->assertCount(1, $resolved);
        $resolvedValue = $resolved[0];
        $this->assertEquals($resolvedValue->getAccountGroup(), $group);
        $this->assertEquals(
            $resolvedValue->getCategoryId(),
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId()
        );
        $this->assertEquals($resolvedValue->getWebsite(), $agpv->getWebsite());
        $this->assertEquals($resolvedValue->getProduct(), $agpv->getProduct());
        $this->assertEquals($resolvedValue->getVisibility(), $visibilityValue);
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

    public function testInsertStatic()
    {
        $this->getRepository()->clearTable();
        $this->getRepository()->insertStatic($this->getInsertFromSelectExecutor());
        $resolved = $this->getResolvedValues();
        $this->assertCount(3, $resolved);
        foreach ($resolved as $resolvedValue) {
            $source = $this->registry
                ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
                ->findOneBy(
                    [
                        'product' => $resolvedValue->getProduct(),
                        'accountGroup' => $resolvedValue->getAccountGroup(),
                        'website' => $resolvedValue->getWebsite(),
                    ]
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
     * @return AccountGroupProductVisibilityResolvedRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
    }
}
