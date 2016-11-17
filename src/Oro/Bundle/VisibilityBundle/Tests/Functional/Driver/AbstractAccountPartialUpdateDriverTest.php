<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Driver;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\VisibilityBundle\Driver\AccountPartialUpdateDriverInterface;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\AccountProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;

/**
 * @dbIsolationPerTest
 */
abstract class AbstractAccountPartialUpdateDriverTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;

    const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.product_visibility';
    const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.category_visibility';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var AccountPartialUpdateDriverInterface
     */
    private $driver;

    protected function setUp()
    {
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        if (!$this->isTestSkipped()) {
            $this->loadFixtures([LoadProductVisibilityData::class]);

            $anonymousGroupId = $this->getContainer()
                ->get('oro_config.global')
                ->get('oro_customer.anonymous_account_group');

            $this->configManager = $this->getContainer()->get('oro_config.global');
            $this->configManager->set('oro_customer.anonymous_account_group', $anonymousGroupId);

            $this->driver = $this->getContainer()->get('oro_website_search.driver.account_partial_update_driver');
            $this->getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
        }
    }

    /**
     * @return bool
     */
    abstract protected function isTestSkipped();

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
            ->select('sku')
            ->from('oro_product_WEBSITE_ID')
            ->getCriteria()
            ->andWhere(Criteria::expr()->exists($this->getVisibilityAccountFieldName($account)))
            ->orderBy(['sku' => Criteria::ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');

        return $searchEngine->search($query);
    }

    private function reindexProducts()
    {
        $this->getContainer()->get('event_dispatcher')->dispatch(
            ReindexationRequestEvent::EVENT_NAME,
            new ReindexationRequestEvent([Product::class], [], [], false)
        );
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
        $this->assertStringStartsWith('product.2', $values[0]->getSelectedData()['sku']);
        $this->assertStringStartsWith('product.3', $values[1]->getSelectedData()['sku']);
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
        $this->assertStringStartsWith('product.2', $values[0]->getSelectedData()['sku']);
        $this->assertStringStartsWith('product.3', $values[1]->getSelectedData()['sku']);

        $visibilityManager = $this
            ->getContainer()
            ->get('doctrine')
            ->getManagerForClass(AccountProductVisibility::class);

        /** @var AccountProductVisibilityRepository $visibilityRepository */
        $visibilityRepository = $visibilityManager->getRepository(AccountProductVisibility::class);

        $scope = $this->getContainer()
            ->get('oro_visibility.provider.visibility_scope_provider')
            ->getAccountProductVisibilityScope($account, $this->getDefaultWebsite());

        /** @var AccountProductVisibility $productVisibility */
        $productVisibility = $visibilityRepository->findOneBy([
            'product' => $this->getReference('product.2'),
            'scope' => $scope
        ]);

        $productVisibility->setVisibility(VisibilityInterface::VISIBLE);
        $visibilityManager->persist($productVisibility);
        $visibilityManager->flush();

        $this->getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();

        $this->driver->updateAccountVisibility($account);

        $searchResult = $this->searchVisibilitiesForAccount($account);
        $values = $searchResult->getElements();

        $this->assertEquals(1, $searchResult->getRecordsCount());
        $this->assertStringStartsWith('product.3', $values[0]->getSelectedData()['sku']);
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
        $this->assertStringStartsWith('product.2', $values[0]->getSelectedData()['sku']);
        $this->assertStringStartsWith('product.3', $values[1]->getSelectedData()['sku']);

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(Account::class);
        $manager->remove($account);
        $manager->flush();

        $searchResult = $this->searchVisibilitiesForAccount($account);

        $this->assertEquals(0, $searchResult->getRecordsCount());
    }
}
