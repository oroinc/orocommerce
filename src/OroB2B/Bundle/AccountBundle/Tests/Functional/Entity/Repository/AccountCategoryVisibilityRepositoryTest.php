<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository;

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
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountCategoryVisibilities'
            ]
        );
    }

    /**
     * @dataProvider visibilityDataProvider
     *
     * @param array  $accountReferences
     * @param string $categoryReference
     * @param array  $expected
     */
    public function testFindForAccounts($accountReferences, $categoryReference, array $expected)
    {
        $accounts = [];
        foreach ($accountReferences as $accountReference) {
            $accounts[] = $this->getReference($accountReference);
        }
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
                'Test First Level',
                [
                    'account_category_visibility.1',
                ]
            ],
            [
                [
                    'account.level_1',
                ],
                'Test Second Level',
                [
                    'account_category_visibility.2',
                ]
            ],
            [
                [
                    'account.level_1',
                    'account.level_1.4',
                ],
                'Test Second Level',
                [
                    'account_category_visibility.2',
                    'account_category_visibility.3',
                ]
            ],
        ];
    }
}
