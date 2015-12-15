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
            ]
        );
    }

    /**
     * @dataProvider getCategoryAccountGroupsByVisibilityDataProvider
     * @param string $categoryName
     * @param string $visibility
     * @param array $expectedAccountGroups
     */
    public function testGetCategoryAccountGroupsByVisibility(
        $categoryName,
        $visibility,
        array $expectedAccountGroups
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $accountGroups = $this->repository->getCategoryAccountGroupsByVisibility($category, $visibility);

        $accountGroups = array_map(
            function (AccountGroup $accountGroup) {
                return $accountGroup->getName();
            },
            $accountGroups
        );

        sort($accountGroups);
        $this->assertEquals($expectedAccountGroups, $accountGroups);
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
