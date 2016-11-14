<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountGroupCategoryVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;

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
        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(AccountGroupCategoryVisibility::class);

        $this->loadFixtures(
            [
                LoadGroups::class,
                LoadCategoryData::class,
                LoadCategoryVisibilityData::class
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
