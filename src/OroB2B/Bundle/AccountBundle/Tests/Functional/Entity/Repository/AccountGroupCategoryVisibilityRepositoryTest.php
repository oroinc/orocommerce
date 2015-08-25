<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

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
            ->getRepository('OroB2BAccountBundle:AccountGroupCategoryVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountGroupCategoryVisibilities'
            ]
        );
    }

    /**
     * @dataProvider visibilityDataProvider
     *
     * @param array  $accountGroupReferences
     * @param string $categoryReference
     * @param array  $expected
     */
    public function testFindForAccountGroups($accountGroupReferences, $categoryReference, array $expected)
    {
        $accountGroups = [];
        foreach ($accountGroupReferences as $accountGroupReference) {
            $accountGroups[] = $this->getReference($accountGroupReference);
        }
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        $actual = $this->repository->findForAccountGroups($accountGroups, $category);
        $this->assertCount(count($expected), $actual);

        foreach ($expected as $i => $visibilityReference) {
            $this->assertEquals($this->getReference($visibilityReference), $actual[$i]);
        }
    }

    /**
     * @return array
     */
    public function visibilityDataProvider()
    {
        return [
            [
                [
                    'account_group.group1',
                ],
                'Test First Level',
                [
                    'account_group_category_visibility.1',
                ]
            ],
            [
                [
                    'account_group.group1',
                ],
                'Test Second Level',
                [
                    'account_group_category_visibility.2',
                ]
            ],
            [
                [
                    'account_group.group1',
                    'account_group.group2',
                ],
                'Test Second Level',
                [
                    'account_group_category_visibility.2',
                    'account_group_category_visibility.3',
                ]
            ],
        ];
    }
}
