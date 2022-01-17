<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Driver;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CustomerProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityScopedData;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use PHPUnit\Framework\SyntheticError;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
abstract class AbstractCustomerPartialUpdateDriverTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;
    use ConfigManagerAwareTestTrait;

    const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.product_visibility';
    const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.category_visibility';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var CustomerPartialUpdateDriverInterface
     */
    private $driver;

    protected function setUp(): void
    {
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->checkTestToBeSkipped();

        $this->loadFixtures([LoadProductVisibilityScopedData::class]);

        $this->configManager = self::getConfigManager('global');
        $this->driver = $this->getContainer()->get('oro_website_search.driver.customer_partial_update_driver');
        $this->getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
    }

    /**
     * @throws SyntheticError
     */
    abstract protected function checkTestToBeSkipped();

    /**
     * @param Customer $customer
     * @return string
     */
    private function getVisibilityCustomerFieldName(Customer $customer)
    {
        return 'integer.visibility_customer.' . $customer->getId();
    }

    /**
     * @param Customer $customer
     * @return Result
     */
    private function searchVisibilitiesForCustomer(Customer $customer)
    {
        $query = new Query();
        $query
            ->select('sku')
            ->from('oro_product_WEBSITE_ID')
            ->getCriteria()
            ->andWhere(Criteria::expr()->exists($this->getVisibilityCustomerFieldName($customer)))
            ->orderBy(['sku' => Criteria::ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');

        return $searchEngine->search($query);
    }

    private function reindexProducts()
    {
        $this->getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    public function testCreateCustomerWithoutCustomerGroupVisibility()
    {
        $this->configManager->set(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::HIDDEN);
        $this->configManager->set(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::HIDDEN);

        $this->reindexProducts();

        /** @var Customer $customerLevel1 */
        $customerLevel1 = $this->getReference('customer.level_1');
        $owner = $customerLevel1->getOwner();

        $customer = new Customer();
        $customer
            ->setName('New Customer')
            ->setOwner($owner)
            ->setOrganization($owner->getOrganization());

        $searchResult = $this->searchVisibilitiesForCustomer($customer);
        $this->assertEquals(0, $searchResult->getRecordsCount());

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(Customer::class);
        $manager->persist($customer);
        $manager->flush();

        $searchResult = $this->searchVisibilitiesForCustomer($customer);
        $values = $searchResult->getElements();

        $this->assertEquals(1, $searchResult->getRecordsCount());
        $this->assertStringStartsWith('product-3', $values[0]->getSelectedData()['sku']);
    }

    public function testUpdateCustomerVisibility()
    {
        $this->configManager->set(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::VISIBLE);
        $this->configManager->set(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::VISIBLE);

        $this->reindexProducts();

        $customer = $this->getReference('customer.level_1');

        $searchResult = $this->searchVisibilitiesForCustomer($customer);
        $values = $searchResult->getElements();

        $this->assertEquals(1, $searchResult->getRecordsCount());
        $this->assertStringStartsWith('product-3', $values[0]->getSelectedData()['sku']);

        $visibilityManager = $this
            ->getContainer()
            ->get('doctrine')
            ->getManagerForClass(CustomerProductVisibility::class);

        /** @var CustomerProductVisibilityRepository $visibilityRepository */
        $visibilityRepository = $visibilityManager->getRepository(CustomerProductVisibility::class);

        $scope = $this->getContainer()
            ->get('oro_visibility.provider.visibility_scope_provider')
            ->getCustomerProductVisibilityScope($customer, $this->getDefaultWebsite());

        /** @var CustomerProductVisibility $productVisibility */
        $productVisibility = $visibilityRepository->findOneBy([
            'product' => $this->getReference('product-3'),
            'scope' => $scope
        ]);

        $productVisibility->setVisibility(VisibilityInterface::VISIBLE);
        $visibilityManager->persist($productVisibility);
        $visibilityManager->flush();

        $this->getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();

        $this->driver->updateCustomerVisibility($customer);

        $searchResult = $this->searchVisibilitiesForCustomer($customer);

        $this->assertEmpty($searchResult->getRecordsCount());
    }

    public function testDeleteCustomerVisibility()
    {
        $this->configManager->set(self::PRODUCT_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::VISIBLE);
        $this->configManager->set(self::CATEGORY_VISIBILITY_CONFIGURATION_PATH, VisibilityInterface::VISIBLE);

        $this->reindexProducts();

        $customer = $this->getReference('customer.level_1');

        $searchResult = $this->searchVisibilitiesForCustomer($customer);
        $values = $searchResult->getElements();

        $this->assertEquals(1, $searchResult->getRecordsCount());
        $this->assertStringStartsWith('product-3', $values[0]->getSelectedData()['sku']);

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(Customer::class);
        $manager->remove($customer);
        $manager->flush();

        $searchResult = $this->searchVisibilitiesForCustomer($customer);

        $this->assertEquals(0, $searchResult->getRecordsCount());
    }
}
