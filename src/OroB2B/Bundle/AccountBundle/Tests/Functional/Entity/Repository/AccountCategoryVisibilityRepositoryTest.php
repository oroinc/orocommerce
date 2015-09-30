<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountCategoryVisibilities;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountCategoryVisibilityRepositoryTest extends WebTestCase
{
    /**
     * @var AccountCategoryVisibilityRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountCategoryVisibilities',
            ]
        );
    }

    /**
     * @dataProvider visibilityDataProvider
     *
     * @param array $accountReferences
     * @param string $categoryReference
     * @param array $expected
     */
    public function testFindForAccounts($accountReferences, $categoryReference, array $expected)
    {
        $accounts = [];
        foreach ($accountReferences as $accountReference) {
            $accounts[] = $this->getReference($accountReference);
        }
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        $actual = $this->repository->findForAccounts($accounts, $category);
        $this->assertCount(count($expected), $actual);

        foreach ($expected as $i => $visibilityReference) {
            /** @var AccountCategoryVisibility $a */
            $visibility = $this->getReference($visibilityReference);
            $this->assertAccountEquals($visibility->getAccount(), $actual[$i]->getAccount());
            $this->assertEquals($visibility->getVisibility(), $actual[$i]->getVisibility());
            $this->assertCategoryEquals($visibility->getCategory(), $actual[$i]->getCategory());
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
                    'account.level_1',
                ],
                LoadCategoryData::FIRST_LEVEL,
                [
                    LoadAccountCategoryVisibilities::VISIBILITY1,
                ],
            ],
            [
                [
                    'account.level_1',
                ],
                LoadCategoryData::SECOND_LEVEL1,
                [
                    LoadAccountCategoryVisibilities::VISIBILITY2,
                ],
            ],
            [
                [
                    'account.level_1',
                    'account.level_1.4',
                ],
                LoadCategoryData::SECOND_LEVEL1,
                [
                    LoadAccountCategoryVisibilities::VISIBILITY2,
                    LoadAccountCategoryVisibilities::VISIBILITY3,
                ],
            ],
        ];
    }

    /**
     * @param Account $expected
     * @param Account $actual
     */
    public function assertAccountEquals(Account $expected, Account $actual)
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
