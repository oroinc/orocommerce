<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
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
        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroAccountBundle:Account');

        $this->loadFixtures(
            [
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                'Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData'
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
        /** @var Account[] $results */
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
