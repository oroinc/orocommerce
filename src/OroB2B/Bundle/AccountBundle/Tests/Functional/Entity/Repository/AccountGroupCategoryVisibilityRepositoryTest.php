<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountGroupCategoryVisibilities;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
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
            ->getRepository('OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountGroupCategoryVisibilities',
            ]
        );
    }

    /**
     * @dataProvider visibilityDataProvider
     *
     * @param array $accountGroupReferences
     * @param string $categoryReference
     * @param array $expected
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
        $ids = [];
        foreach ($actual as $actualVisibility) {
            $ids[] = $actualVisibility->getId();
        }
        foreach ($expected as $visibilityReference) {
            /** @var AccountGroupCategoryVisibility $expectedVisibility */
            $expectedVisibility = $this->getReference($visibilityReference);
            $this->assertContains($expectedVisibility->getId(), $ids);
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
                LoadCategoryData::FIRST_LEVEL,
                [
                    LoadAccountGroupCategoryVisibilities::VISIBILITY1,
                ],
            ],
            [
                [
                    'account_group.group1',
                ],
                LoadCategoryData::SECOND_LEVEL1,
                [
                    LoadAccountGroupCategoryVisibilities::VISIBILITY2,
                ],
            ],
            [
                [
                    'account_group.group1',
                    'account_group.group2',
                ],
                LoadCategoryData::SECOND_LEVEL1,
                [
                    LoadAccountGroupCategoryVisibilities::VISIBILITY2,
                    LoadAccountGroupCategoryVisibilities::VISIBILITY3,
                ],
            ],
        ];
    }

    /**
     * @param AccountGroup $expected
     * @param AccountGroup $actual
     */
    public function assertAccountGroupEquals(AccountGroup $expected, AccountGroup $actual)
    {
        $this->assertEquals($expected->getName(), $actual->getName());
    }

    /**
     * @param Category $expected
     * @param Category $actual
     */
    public function assertCategoryEquals(Category $expected, Category $actual)
    {
        $this->assertEquals($expected->getDefaultTitle()->getString(), $actual->getDefaultTitle()->getString());
    }
}
