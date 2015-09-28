<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountCategoryVisibilities;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
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
            ->getRepository('OroB2BAccountBundle:AccountCategoryVisibility');

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
}
