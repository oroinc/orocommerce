<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Migrations\Data\ORM\LoadAnonymousAccountGroup;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountGroupRepositoryTest extends WebTestCase
{
    /**
     * @var AccountGroupRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:AccountGroup');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
            ]
        );
    }

    /**
     * @dataProvider getCategoryAccountGroupIdsByVisibilityDataProvider
     * @param string $categoryName
     * @param string $visibility
     * @param array $expectedAccountGroups
     * @param array $restricted
     */
    public function testGetCategoryAccountGroupIdsByVisibility(
        $categoryName,
        $visibility,
        array $expectedAccountGroups,
        array $restricted = null
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $accountGroupIds = $this->repository->getCategoryAccountGroupIdsByVisibility(
            $category,
            $visibility,
            $restricted
        );

        $expectedAccountGroupIds = [];
        foreach ($expectedAccountGroups as $expectedAccountGroupName) {
            $accountGroup = $this->getReference($expectedAccountGroupName);
            $expectedAccountGroupIds[] = $accountGroup->getId();
        }

        sort($expectedAccountGroupIds);
        sort($accountGroupIds);

        $this->assertEquals($expectedAccountGroupIds, $accountGroupIds);
    }

    public function testGetBatchIterator()
    {
        $expectedNames = [
            LoadAnonymousAccountGroup::GROUP_NAME_NON_AUTHENTICATED,
            LoadGroups::GROUP1,
            LoadGroups::GROUP2,
            LoadGroups::GROUP3,
        ];

        $accountGroupsIterator = $this->repository->getBatchIterator();
        $accountGroupNames = [];
        foreach ($accountGroupsIterator as $accountGroup) {
            $accountGroupNames[] = $accountGroup->getName();
        }

        $this->assertEquals($expectedNames, $accountGroupNames);
    }

    /**
     * @return array
     */
    public function getCategoryAccountGroupIdsByVisibilityDataProvider()
    {
        return [
            'FIRST_LEVEL with HIDDEN' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => AccountGroupCategoryVisibility::HIDDEN,
                'expectedAccountGroups' => [
                    'account_group.group1'
                ]
            ],
            'FIRST_LEVEL with VISIBLE restricted' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => AccountGroupCategoryVisibility::VISIBLE,
                'expectedAccountGroups' => [],
                'restricted' => []
            ],
            'FOURTH_LEVEL1 with PARENT_CATEGORY' => [
                'categoryName' => LoadCategoryData::FOURTH_LEVEL1,
                'visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                'expectedAccountGroups' => [
                    'account_group.group1',
                    'account_group.group3',
                ]
            ],
        ];
    }
}
