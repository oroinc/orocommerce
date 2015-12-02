<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\AccountGroupCategoryVisibilityRepository;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountGroupCategoryVisibilityRepositoryTest extends WebTestCase
{
    /**
     * @var AccountGroupCategoryVisibilityRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility');

        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData']);
    }

    /**
     * dataProvider getVisibilityToAllDataProvider
     */
    public function testGetVisibilityToAll()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');
        /** @var array $actualItems */
        $actualItems = $this->repository->getCategoryWithVisibilitiesForAccountGroup($accountGroup)
            ->addOrderBy('c.left')
            ->getQuery()->execute();

        $root = $this->getContainer()->get('doctrine')->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
        $expectedItems = [
            [
                'category_id' => $root->getId(),
                'category_parent_id' => null,
                'visibility' => null,
                'account_group_visibility' => null,
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
                'category_parent_id' => $root->getId(),
                'visibility' => CategoryVisibility::VISIBLE,
                'account_group_visibility' => AccountGroupCategoryVisibility::HIDDEN,
                'account_visibility' => AccountCategoryVisibility::PARENT_CATEGORY,
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::SECOND_LEVEL1)->getId(),
                'category_parent_id' => $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
                'visibility' => null,
                'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::SECOND_LEVEL2)->getId(),
                'category_parent_id' => $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId(),
                'visibility' => null,
                'account_group_visibility' => null,
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::THIRD_LEVEL1)->getId(),
                'category_parent_id' => $this->getReference(LoadCategoryData::SECOND_LEVEL1)->getId(),
                'visibility' => CategoryVisibility::VISIBLE,
                'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::THIRD_LEVEL2)->getId(),
                'category_parent_id' => $this->getReference(LoadCategoryData::SECOND_LEVEL2)->getId(),
                'visibility' => CategoryVisibility::HIDDEN,
                'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                'account_visibility' => AccountCategoryVisibility::CATEGORY,
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::FOURTH_LEVEL1)->getId(),
                'category_parent_id' => $this->getReference(LoadCategoryData::THIRD_LEVEL1)->getId(),
                'visibility' => null,
                'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
            ],
            [
                'category_id' => $this->getReference(LoadCategoryData::FOURTH_LEVEL2)->getId(),
                'category_parent_id' => $this->getReference(LoadCategoryData::THIRD_LEVEL2)->getId(),
                'visibility' => null,
                'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                'account_visibility' => AccountCategoryVisibility::HIDDEN,
            ]
        ];
        $this->assertCount(count($expectedItems), $actualItems);
        foreach ($actualItems as $i => $actual) {
            $this->assertArrayHasKey($i, $expectedItems);
            $expected = $expectedItems[$i];
            $this->assertEquals($expected['category_id'], $actual['category_id']);
            $this->assertEquals($expected['category_parent_id'], $actual['category_parent_id']);
            $this->assertEquals($expected['visibility'], $actual['visibility']);
            $this->assertEquals($expected['account_group_visibility'], $actual['account_group_visibility']);
        }
    }

    /**
     * @return array
     */
    public function getVisibilityToAllDataProvider()
    {
        return [];
    }
}
