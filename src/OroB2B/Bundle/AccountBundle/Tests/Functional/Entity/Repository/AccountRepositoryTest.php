<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

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
            ->getRepository('OroB2BAccountBundle:Account');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData'
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
     * @dataProvider getCategoryAccountsByVisibilityDataProvider
     * @param string $categoryName
     * @param string $visibility
     * @param array $expectedAccounts
     */
    public function testGetCategoryAccountsByVisibility($categoryName, $visibility, array $expectedAccounts)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $accounts = $this->repository->getCategoryAccountsByVisibility($category, $visibility);

        $accounts = array_map(
            function (Account $account) {
                return $account->getName();
            },
            $accounts
        );

        $this->assertEquals($expectedAccounts, $accounts);
    }

    /**
     * @return array
     */
    public function getCategoryAccountsByVisibilityDataProvider()
    {
        return [
            'FIRST_LEVEL with VISIBLE' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => AccountCategoryVisibility::VISIBLE,
                'expectedAccounts' => [
                    'account.level_1.4',
                ]
            ],
            'FIRST_LEVEL with HIDDEN' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => AccountCategoryVisibility::HIDDEN,
                'expectedAccounts' => []
            ],
        ];
    }
}
