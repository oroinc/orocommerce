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
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountCategoryVisibilities',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountGroupCategoryVisibilities',
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

        $this->assertEquals($expectedAccountGroups, $accountGroups);
    }

    /**
     * @return array
     */
    public function getCategoryAccountGroupsByVisibilityDataProvider()
    {
        return [
            'FIRST_LEVEL with VISIBLE' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => AccountGroupCategoryVisibility::VISIBLE,
                'expectedAccountGroups' => [
                    'account_group.group1'
                ]
            ],
            'SECOND_LEVEL1 with HIDDEN' => [
                'categoryName' => LoadCategoryData::SECOND_LEVEL1,
                'visibility' => AccountGroupCategoryVisibility::HIDDEN,
                'expectedAccountGroups' => [
                    'account_group.group1'
                ]
            ],
        ];
    }
}
