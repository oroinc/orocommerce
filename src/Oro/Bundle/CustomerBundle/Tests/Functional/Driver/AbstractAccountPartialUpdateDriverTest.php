<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Driver;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Driver\AccountPartialUpdateDriverInterface;
use Oro\Bundle\CustomerBundle\Driver\OrmAccountPartialUpdateDriver;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\CustomerBundle\Entity\Visibility\Repository\AccountProductVisibilityRepository;
use Oro\Bundle\CustomerBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;

/**
 * @dbIsolationPerTest
 */
abstract class AbstractAccountPartialUpdateDriverTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;

    const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_customer.product_visibility';
    const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_customer.category_visibility';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var OrmAccountPartialUpdateDriver
     */
    private $driver;

    protected function setUp()
    {
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([LoadProductVisibilityData::class]);

        $anonymousGroupId = $this->getContainer()
            ->get('oro_config.global')
            ->get('oro_customer.anonymous_account_group');

        $this->configManager = $this->getContainer()->get('oro_config.global');
        $this->configManager->set('oro_customer.anonymous_account_group', $anonymousGroupId);

        $this->driver = $this->getDriver();
        $this->getContainer()->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();
    }

    /**
     * @return AccountPartialUpdateDriverInterface
     */
    abstract protected function getDriver();

    /**
     * @param Account $account
     * @return string
     */
    private function getVisibilityAccountFieldName(Account $account)
    {
        return 'integer.visibility_account_' . $account->getId();
    }

    /**
     * @param Account $account
     * @return \Oro\Bundle\SearchBundle\Query\Result
     */
    private function searchVisibilitiesForAccount(Account $account)
    {
        $query = new Query();
        $query
            ->from('oro_product_' . $this->getDefaultWebsiteId())
            ->getCriteria()
            ->andWhere(Criteria::expr()->eq($this->getVisibilityAccountFieldName($account), 1))
            ->orderBy(['sku' => Criteria::ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');

        return $searchEngine->search($query);
    }

    private function reindexProducts()
    {
        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $indexer->reindex(Product::class);
    }

    public function testCreateAccountWithoutAccountGroupVisibility()
    {
        $this->configManager->set(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::HIDDEN);
        $this->configManager->set(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::HIDDEN);

        $this->reindexProducts();

        /** @var Account $accountLevel1 */
        $accountLevel1 = $this->getReference('account.level_1');
        $owner = $accountLevel1->getOwner();

        $account = new Account();
        $account
            ->setName('New Account')
            ->setOwner($owner)
            ->setOrganization($owner->getOrganization());

        $searchResult = $this->searchVisibilitiesForAccount($account);
        $this->assertEquals(0, $searchResult->getRecordsCount());

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(Account::class);
        $manager->persist($account);
        $manager->flush();

        $searchResult = $this->searchVisibilitiesForAccount($account);
        $values = $searchResult->getElements();

        $this->assertEquals(2, $searchResult->getRecordsCount());
        $this->assertStringStartsWith('product.2', $values[0]->getRecordTitle());
        $this->assertStringStartsWith('product.3', $values[1]->getRecordTitle());
    }

    public function testUpdateAccountVisibility()
    {
        $this->configManager->set(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::VISIBLE);
        $this->configManager->set(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::VISIBLE);

        $this->reindexProducts();

        $account = $this->getReference('account.level_1');

        $searchResult = $this->searchVisibilitiesForAccount($account);
        $values = $searchResult->getElements();

        $this->assertEquals(2, $searchResult->getRecordsCount());
        $this->assertStringStartsWith('product.2', $values[0]->getRecordTitle());
        $this->assertStringStartsWith('product.3', $values[1]->getRecordTitle());

        $visibilityManager = $this
            ->getContainer()
            ->get('doctrine')
            ->getManagerForClass(AccountProductVisibility::class);

        /** @var AccountProductVisibilityRepository $visibilityRepository */
        $visibilityRepository = $visibilityManager->getRepository(AccountProductVisibility::class);

        /** @var AccountProductVisibility $productVisibility */
        $productVisibility = $visibilityRepository->findOneBy([
            'website' => $this->getDefaultWebsiteId(),
            'product' => $this->getReference('product.2'),
            'account' => $account
        ]);

        $productVisibility->setVisibility(VisibilityInterface::VISIBLE);
        $visibilityManager->persist($productVisibility);
        $visibilityManager->flush();

        $this->getContainer()->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();

        $this->driver->updateAccountVisibility($account);

        $searchResult = $this->searchVisibilitiesForAccount($account);
        $values = $searchResult->getElements();

        $this->assertEquals(1, $searchResult->getRecordsCount());
        $this->assertStringStartsWith('product.3', $values[0]->getRecordTitle());
    }

    public function testDeleteAccountVisibility()
    {
        $this->configManager->set(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::VISIBLE);
        $this->configManager->set(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::VISIBLE);

        $this->reindexProducts();

        $account = $this->getReference('account.level_1');

        $searchResult = $this->searchVisibilitiesForAccount($account);
        $values = $searchResult->getElements();

        $this->assertEquals(2, $searchResult->getRecordsCount());
        $this->assertStringStartsWith('product.2', $values[0]->getRecordTitle());
        $this->assertStringStartsWith('product.3', $values[1]->getRecordTitle());

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(Account::class);
        $manager->remove($account);
        $manager->flush();

        $searchResult = $this->searchVisibilitiesForAccount($account);

        $this->assertEquals(0, $searchResult->getRecordsCount());
    }
}
