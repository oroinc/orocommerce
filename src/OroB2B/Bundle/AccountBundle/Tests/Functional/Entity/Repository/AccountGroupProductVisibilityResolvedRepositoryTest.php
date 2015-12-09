<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;

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
        /** @var AccountGroup $group */
        $group = $this->getReference(LoadGroups::GROUP1);
        $agpv = $this->registry->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findOneBy(['product' => $this->getReference(LoadProductData::PRODUCT_1), 'accountGroup' => $group]);
        $agpv->setVisibility(AccountGroupProductVisibility::CATEGORY);
        $this->registry->getManager()->flush();
        $this->assertCount(0, $this->getResolvedValues());
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

    public function testUpdateFromBaseTable()
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
            $this->assertEquals(
                $source->getVisibility(),
                $resolvedValue->getVisibility() == BaseProductVisibilityResolved::VISIBILITY_HIDDEN
                    ? AccountGroupProductVisibility::HIDDEN : AccountGroupProductVisibility::VISIBLE
            );
        }

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
