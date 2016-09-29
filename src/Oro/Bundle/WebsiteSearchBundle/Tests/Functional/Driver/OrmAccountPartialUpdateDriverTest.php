<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Driver;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

/**
 * @dbIsolationPerTest
 */
class OrmAccountPartialUpdateDriverTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadProductVisibilityData::class]);

        $this->getContainer()->get('oro_account.visibility.cache.product.cache_builder')->buildCache();
    }

    /**
     * @param string $accountReference
     * @return array
     */
    private function prepareExpectedAccountField($accountReference)
    {
        return [
            'field' => 'visibility_account_' . $this->getReference($accountReference)->getId(),
        ];
    }

    public function testCreateAccountWithoutAccountGroupVisibility()
    {
        /** @var OrmIndexer $indexer */
        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $indexer->reindex(Product::class);

        /** @var Account $accountLevel1 */
        $accountLevel1 = $this->getReference('account.level_1');
        $owner = $accountLevel1->getOwner();

        $account = new Account();
        $account
            ->setName('New Account')
            ->setOwner($owner)
            ->setOrganization($owner->getOrganization());

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(Account::class);
        $manager->persist($account);
        $manager->flush();

        $driver = $this->getContainer()->get('oro_website_search.driver.orm_account_partial_update_driver');
        $driver->createAccountWithoutAccountGroupVisibility($account);

        /*
        $this->assertProductHasFields(
            $this->getReference('product.2')->getId(),
        );
        */
    }

    public function testDeleteAccountVisibility()
    {
        /** @var OrmIndexer $indexer */
        $indexer = $this->getContainer()->get('oro_website_search.indexer');

        $indexer->reindex(Product::class);

        $this->assertProductIndexedAccountVisibilities(
            $this->getReference('product.2')->getId(),
            [
                $this->prepareExpectedAccountField('account.level_1'),
                $this->prepareExpectedAccountField('account.level_1.2'),
                $this->prepareExpectedAccountField('account.level_1.2.1'),
                $this->prepareExpectedAccountField('account.level_1.2.1.1'),
            ]
        );

        $this->assertProductIndexedAccountVisibilities(
            $this->getReference('product.3')->getId(),
            [
                $this->prepareExpectedAccountField('account.level_1'),
                $this->prepareExpectedAccountField('account.level_1.3'),
            ]
        );

        $accountUser = $this->getReference('account.level_1');

        $driver = $this->getContainer()->get('oro_website_search.driver.orm_account_partial_update_driver');
        $driver->deleteAccountVisibility($accountUser);

        $this->assertProductIndexedAccountVisibilities(
            $this->getReference('product.2')->getId(),
            [
                $this->prepareExpectedAccountField('account.level_1.2'),
                $this->prepareExpectedAccountField('account.level_1.2.1'),
                $this->prepareExpectedAccountField('account.level_1.2.1.1'),
            ]
        );

        $this->assertProductIndexedAccountVisibilities(
            $this->getReference('product.3')->getId(),
            [
                $this->prepareExpectedAccountField('account.level_1.3'),
            ]
        );
    }

    /**
     * @param int $productId
     * @param array $expectedFields
     * @param string $fieldPattern
     */
    private function assertProductHasFields($productId, array $expectedFields, $fieldPattern)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var WebsiteSearchIndexRepository $itemRepository */
        $itemRepository = $doctrine
            ->getManagerForClass(Item::class)
            ->getRepository(Item::class);

        $queryBuilder = $itemRepository->createQueryBuilder('item');
        $accountFields = $queryBuilder
            ->select('integerFields.field', 'integerFields.value')
            ->join('item.integerFields', 'integerFields')
            ->andWhere($queryBuilder->expr()->eq('item.entity', ':entityClass'))
            ->andWhere($queryBuilder->expr()->like('integerFields.field', ':fieldPattern'))
            ->andWhere($queryBuilder->expr()->eq('item.recordId', ':productId'))
            ->orderBy('integerFields.field', 'ASC')
            ->setParameters([
                'entityClass' => Product::class,
                'productId' => $productId,
                'fieldPattern' => $fieldPattern
            ])
            ->getQuery()
            ->getScalarResult();

        $existingFields = [];
        foreach ($accountFields as $accountField) {
            $existingFields[$accountField['field']] = $accountField['value'];
        }

        $this->assertEquals($expectedFields, $existingFields);
    }

    /**
     * Asserts that product has in index expected fields for account visibility.
     * Note that index contains account visibility field for accounts which visibility differs
     * from visibility defined by system category visibility configuration option.
     *
     * @param int $productId
     * @param array $expectedFields
     */
    private function assertProductIndexedAccountVisibilities($productId, array $expectedFields)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var WebsiteSearchIndexRepository $itemRepository */
        $itemRepository = $doctrine
            ->getManagerForClass(Item::class)
            ->getRepository(Item::class);

        $queryBuilder = $itemRepository->createQueryBuilder('item');
        $accountFields = $queryBuilder
            ->select('integerFields.field')
            ->join('item.integerFields', 'integerFields')
            ->andWhere($queryBuilder->expr()->eq('item.entity', ':entityClass'))
            ->andWhere($queryBuilder->expr()->like('integerFields.field', ':fieldPattern'))
            ->andWhere($queryBuilder->expr()->eq('item.recordId', ':productId'))
            ->orderBy('integerFields.field', 'ASC')
            ->setParameters([
                'entityClass' => Product::class,
                'productId' => $productId,
                'fieldPattern' => 'visibility_account_%'
            ])
            ->getQuery()
            ->getScalarResult();

        $this->assertEquals($expectedFields, $accountFields);
    }
}
