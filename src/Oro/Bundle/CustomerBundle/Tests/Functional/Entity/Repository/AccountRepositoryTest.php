<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountRepositoryTest extends WebTestCase
{
    /**
     * @var AccountRepository
     */
    protected $repository;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCustomerBundle:Account');

        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData'
            ]
        );

        $this->aclHelper = $this->getContainer()->get('oro_security.acl_helper');
    }

    /**
     * @dataProvider accountReferencesDataProvider
     * @param string $referenceName
     * @param array $expectedReferences
     */
    public function testGetChildrenIds($referenceName, array $expectedReferences)
    {
        /** @var Account $account */
        $account = $this->getReference($referenceName);

        $expected = [];
        foreach ($expectedReferences as $reference) {
            $expected[] = $this->getReference($reference)->getId();
        }
        $childrenIds = $this->repository->getChildrenIds($this->aclHelper, $account->getId());
        sort($expected);
        sort($childrenIds);

        $this->assertEquals($expected, $childrenIds);
    }

    /**
     * @return array
     */
    public function accountReferencesDataProvider()
    {
        return [
            'orphan' => [
                'account.orphan',
                []
            ],
            'level_1' => [
                'account.level_1',
                [
                    'account.level_1.1',
                    'account.level_1.1.1',
                    'account.level_1.2',
                    'account.level_1.2.1',
                    'account.level_1.2.1.1',
                    'account.level_1.3',
                    'account.level_1.3.1',
                    'account.level_1.3.1.1',
                    'account.level_1.4',
                    'account.level_1.4.1',
                    'account.level_1.4.1.1',
                ]
            ],
            'level_1.1' => [
                'account.level_1.1',
                [
                    'account.level_1.1.1'
                ]
            ],
            'level_1.2' => [
                'account.level_1.2',
                [
                    'account.level_1.2.1',
                    'account.level_1.2.1.1',
                ]
            ],
            'level_1.3' => [
                'account.level_1.3',
                [
                    'account.level_1.3.1',
                    'account.level_1.3.1.1',
                ]
            ],
            'level_1.4' => [
                'account.level_1.4',
                [
                    'account.level_1.4.1',
                    'account.level_1.4.1.1',
                ]
            ],
        ];
    }

    /**
     * @dataProvider getCategoryAccountIdsByVisibilityDataProvider
     * @param string $categoryName
     * @param string $visibility
     * @param array $expectedAccounts
     * @param array $restricted
     */
    public function testGetCategoryAccountIdsByVisibility(
        $categoryName,
        $visibility,
        array $expectedAccounts,
        array $restricted = null
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $accountIds = $this->repository->getCategoryAccountIdsByVisibility($category, $visibility, $restricted);

        $expectedAccountIds = [];
        foreach ($expectedAccounts as $expectedAccountName) {
            $accountGroup = $this->getReference($expectedAccountName);
            $expectedAccountIds[] = $accountGroup->getId();
        }

        $this->assertEquals($expectedAccountIds, $accountIds);
    }

    /**
     * @return array
     */
    public function getCategoryAccountIdsByVisibilityDataProvider()
    {
        return [
            'FIRST_LEVEL with VISIBLE' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => AccountCategoryVisibility::VISIBLE,
                'expectedAccounts' => [
                    'account.level_1.4',
                ]
            ],
            'FIRST_LEVEL with VISIBLE restricted' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => AccountCategoryVisibility::VISIBLE,
                'expectedAccounts' => [],
                'restricted' => []
            ],
            'FIRST_LEVEL with HIDDEN' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => AccountCategoryVisibility::HIDDEN,
                'expectedAccounts' => [
                    'account.level_1.1',
                ]
            ],
        ];
    }

    public function testGetBatchIterator()
    {
        $results  = $this->repository->findAll();
        $accounts = [];

        foreach ($results as $account) {
            $accounts[$account->getId()] = $account;
        }

        $accountsQuantity = count($accounts);
        $accountsIterator = $this->repository->getBatchIterator();
        $iteratorQuantity = 0;
        foreach ($accountsIterator as $account) {
            ++$iteratorQuantity;
            unset($accounts[$account->getId()]);
        }

        $this->assertEquals($accountsQuantity, $iteratorQuantity);
        $this->assertEmpty($accounts);
    }
}
