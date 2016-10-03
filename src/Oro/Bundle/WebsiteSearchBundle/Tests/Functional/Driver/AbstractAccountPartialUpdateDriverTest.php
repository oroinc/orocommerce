<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Driver;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\EventListener\WebsiteSearchProductVisibilityIndexerListener;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\AccountBundle\Visibility\Provider\AccountProductVisibilityProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Driver\AccountPartialUpdateDriverInterface;
use Oro\Bundle\WebsiteSearchBundle\Driver\OrmAccountPartialUpdateDriver;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\AbstractSearchWebTestCase;

/**
 * @dbIsolationPerTest
 */
abstract class AbstractAccountPartialUpdateDriverTest extends AbstractSearchWebTestCase
{
    const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_account.product_visibility';
    const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_account.category_visibility';

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var OrmAccountPartialUpdateDriver */
    private $driver;

    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadProductVisibilityData::class]);

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityProvider = new AccountProductVisibilityProvider(
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $this->configManager
        );

        $accountProductVisibilityProvider
            ->setProductVisibilitySystemConfigurationPath('oro_account.product_visibility');
        $accountProductVisibilityProvider
            ->setCategoryVisibilitySystemConfigurationPath('oro_account.category_visibility');

        $this->driver = $this->createDriver($accountProductVisibilityProvider);
        $this->getContainer()->get('oro_account.visibility.cache.product.cache_builder')->buildCache();

        $eventName = 'oro_website_search.event.index_entity';

        $listener = new WebsiteSearchProductVisibilityIndexerListener(
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $accountProductVisibilityProvider
        );

        $this->dispatcher->removeListener($eventName, [
            $this->getContainer()->get('oro_account.event_listener.website_search_product_visibility_indexer_listener'),
            'onWebsiteSearchIndex'
        ]);

        $this->dispatcher->addListener(
            $eventName,
            [
                $listener,
                'onWebsiteSearchIndex'
            ],
            -255
        );
    }

    /**
     * @return AccountPartialUpdateDriverInterface
     */
    abstract protected function createDriver(AccountProductVisibilityProvider $accountProductVisibilityProvider);

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
        $this->configManager
            ->expects($this->exactly(6))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH],
                ['oro_account.anonymous_account_group'],
                ['oro_account.anonymous_account_group'],
                ['oro_account.anonymous_account_group']
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::HIDDEN, VisibilityInterface::HIDDEN, 1, 1, 1, 1);

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

        $visibilityAccountFieldName = 'integer.visibility_account_' . $account->getId();
        $query = new Query();
        $query
            ->from('oro_product_' . $this->getDefaultWebsiteId())
            ->getCriteria()
            ->andWhere(Criteria::expr()->eq($visibilityAccountFieldName, 1))
            ->orderBy(['sku' => Criteria::ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');

        $results = $searchEngine->search($query);
        $this->assertEquals(0, $results->getRecordsCount());

        $this->driver->createAccountWithoutAccountGroupVisibility($account);

        $results = $searchEngine->search($query);
        $values = $results->getElements();
        $this->assertEquals(2, $results->getRecordsCount());
        $this->assertEquals('product.2', $values[0]->getRecordTitle());
        $this->assertEquals('product.3', $values[1]->getRecordTitle());

        //--Debugging
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var WebsiteSearchIndexRepository $itemRepository */
        $repository = $doctrine
            ->getManagerForClass(IndexInteger::class)
            ->getRepository(IndexInteger::class);

        $fieldsVis = $repository->findBy([
            'field' => 'visibility_account_' . $account->getId(),
        ]);

        // Duplicated values in integer fields!!!
        // Test once more cause the bug is not reproduced
        $fieldsNew = $repository->findBy([
            'field' => 'visibility_new',
            'value' => 1
        ]);
    }

    public function testUpdateAccountVisibility()
    {
        $this->configManager
            ->expects($this->exactly(6))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH],
                ['oro_account.anonymous_account_group'],
                ['oro_account.anonymous_account_group'],
                ['oro_account.anonymous_account_group']
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::VISIBLE, VisibilityInterface::VISIBLE, 1, 1, 1, 1);

        /** @var OrmIndexer $indexer */
        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $indexer->reindex(Product::class);

        $accountUser = $this->getReference('account.level_1');

        $visibilityAccountFieldName = 'integer.visibility_account_' . $accountUser->getId();

        $query = new Query();
        $query
            ->from('oro_product_' . $this->getDefaultWebsiteId())
            ->getCriteria()
            ->andWhere(Criteria::expr()->eq($visibilityAccountFieldName, 1))
            ->orderBy(['sku' => Criteria::ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');

        $results = $searchEngine->search($query);
        $values = $results->getElements();

        $this->assertEquals(2, $results->getRecordsCount());
        $this->assertEquals('product.2', $values[0]->getRecordTitle());
        $this->assertEquals('product.3', $values[1]->getRecordTitle());

        $visibilityManager = $this
            ->getContainer()
            ->get('doctrine')
            ->getManagerForClass(AccountProductVisibility::class);

        $visibilityRepository = $visibilityManager->getRepository(AccountProductVisibility::class);

        $productVisibility = $visibilityRepository->findOneBy([
            'website' => $this->getDefaultWebsiteId(),
            'product' => $this->getReference('product.2'),
            'account' => $accountUser
        ]);

        $productVisibility->setVisibility(VisibilityInterface::VISIBLE);
        $visibilityManager->persist($productVisibility);
        $visibilityManager->flush();

        $this->getContainer()->get('oro_account.visibility.cache.product.cache_builder')->buildCache();

        $this->driver->updateAccountVisibility($accountUser);

        $productVisibility = $visibilityRepository->findOneBy([
            'website' => $this->getDefaultWebsiteId(),
            'product' => $this->getReference('product.2'),
            'account' => $accountUser
        ]);

        $query = new Query();
        $query
            ->from('oro_product_' . $this->getDefaultWebsiteId())
            ->getCriteria()
            ->andWhere(Criteria::expr()->eq($visibilityAccountFieldName, 1))
            ->orderBy(['sku' => Criteria::ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');

        $results = $searchEngine->search($query);
        $values = $results->getElements();

        $this->assertEquals(1, $results->getRecordsCount());
        $this->assertEquals('product.3', $values[0]->getRecordTitle());
    }

    public function testDeleteAccountVisibility()
    {
        $this->configManager
            ->expects($this->exactly(6))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH],
                ['oro_account.anonymous_account_group'],
                ['oro_account.anonymous_account_group'],
                ['oro_account.anonymous_account_group']
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::VISIBLE, VisibilityInterface::VISIBLE, 1, 1, 1, 1);

        /** @var OrmIndexer $indexer */
        $indexer = $this->getContainer()->get('oro_website_search.indexer');

        $indexer->reindex(Product::class);

        $accountUser = $this->getReference('account.level_1');
        $visibilityAccountFieldName = 'integer.visibility_account_' . $accountUser->getId();

        $query = new Query();
        $query
            ->from('oro_product_' . $this->getDefaultWebsiteId())
            ->getCriteria()
            ->andWhere(Criteria::expr()->eq($visibilityAccountFieldName, 1))
            ->orderBy(['sku' => Criteria::ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');

        $results = $searchEngine->search($query);
        $values = $results->getElements();

        $this->assertEquals(2, $results->getRecordsCount());
        $this->assertEquals('product.2', $values[0]->getRecordTitle());
        $this->assertEquals('product.3', $values[1]->getRecordTitle());

        $this->driver->deleteAccountVisibility($accountUser);

        $results = $searchEngine->search($query);
        $this->assertEquals(0, $results->getRecordsCount());
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
