<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
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
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountGroupCategoryVisibilities',
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

        $this->assertEquals($expectedAccountGroupIds, $accountGroupIds);
    }

    /**
     * @return array
     */
    public function getCategoryAccountGroupsByVisibilityDataProvider()
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
