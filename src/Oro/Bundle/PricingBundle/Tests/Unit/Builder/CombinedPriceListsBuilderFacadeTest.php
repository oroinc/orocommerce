<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Builder\CustomerCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\CustomerGroupCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Builder\WebsiteCombinedPriceListsBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Model\DTO\CustomerWebsiteDTO;
use Oro\Bundle\PricingBundle\PricingStrategy\PriceCombiningStrategyInterface;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CombinedPriceListsBuilderFacadeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CustomerCombinedPriceListsBuilder */
    private $customerCombinedPriceListBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CustomerGroupCombinedPriceListsBuilder */
    private $customerGroupCombinedPriceListBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WebsiteCombinedPriceListsBuilder */
    private $websiteCombinedPriceListBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CombinedPriceListsBuilder */
    private $combinedPriceListBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $dispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|StrategyRegister */
    private $strategyRegister;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PriceListToWebsiteRepository */
    private $priceListToWebsiteRepo;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PriceListToCustomerGroupRepository */
    private $priceListToCustomerGroupRepo;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PriceListToCustomerRepository */
    private $priceListToCustomerRepo;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CombinedPriceListGarbageCollector */
    private $garbageCollector;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var CombinedPriceListsBuilderFacade */
    private $facade;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->customerCombinedPriceListBuilder = $this->createMock(CustomerCombinedPriceListsBuilder::class);
        $this->customerGroupCombinedPriceListBuilder = $this->createMock(CustomerGroupCombinedPriceListsBuilder::class);
        $this->websiteCombinedPriceListBuilder = $this->createMock(WebsiteCombinedPriceListsBuilder::class);
        $this->combinedPriceListBuilder = $this->createMock(CombinedPriceListsBuilder::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->strategyRegister = $this->createMock(StrategyRegister::class);
        $this->garbageCollector = $this->createMock(CombinedPriceListGarbageCollector::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->facade = new CombinedPriceListsBuilderFacade(
            $this->doctrineHelper,
            $this->customerCombinedPriceListBuilder,
            $this->customerGroupCombinedPriceListBuilder,
            $this->websiteCombinedPriceListBuilder,
            $this->combinedPriceListBuilder,
            $this->dispatcher,
            $this->strategyRegister,
            $this->garbageCollector,
            $this->configManager
        );

        $this->priceListToWebsiteRepo = $this->createMock(PriceListToWebsiteRepository::class);
        $this->priceListToCustomerGroupRepo = $this->createMock(PriceListToCustomerGroupRepository::class);
        $this->priceListToCustomerRepo = $this->createMock(PriceListToCustomerRepository::class);
    }

    public function testRebuild()
    {
        $combinedPriceList1 = $this->getEntity(CombinedPriceList::class, ['id' => 11]);
        $combinedPriceList2 = $this->getEntity(CombinedPriceList::class, ['id' => 22]);
        $combinedPriceList3 = $this->getEntity(CombinedPriceList::class, ['id' => 33]);
        $combinedPriceLists = [$combinedPriceList1, $combinedPriceList2, $combinedPriceList3];

        $products = [new Product(), new Product(), new Product()];

        $startTimestamp = time();

        /** @var \PHPUnit\Framework\MockObject\MockObject|PriceCombiningStrategyInterface $strategy */
        $strategy = $this->createMock(PriceCombiningStrategyInterface::class);
        $this->strategyRegister->expects($this->once())
            ->method('getCurrentStrategy')
            ->willReturn($strategy);

        $strategy->expects($this->exactly(3))
            ->method('combinePrices')
            ->withConsecutive(
                [$combinedPriceList1, $products, $startTimestamp],
                [$combinedPriceList2, $products, $startTimestamp],
                [$combinedPriceList3, $products, $startTimestamp]
            );

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new CombinedPriceListsUpdateEvent([11, 22, 33]), CombinedPriceListsUpdateEvent::NAME);

        $this->facade->rebuild($combinedPriceLists, $products, $startTimestamp);
        $this->facade->dispatchEvents();
    }

    public function testRebuildAll()
    {
        $forceTimestamp = time();
        $websiteId = 1;
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 1]);
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 2]);
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 3]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [PriceListToWebsite::class, $this->priceListToWebsiteRepo],
                [PriceListToCustomerGroup::class, $this->priceListToCustomerGroupRepo],
                [PriceListToCustomer::class, $this->priceListToCustomerRepo],
            ]);

        $this->combinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($forceTimestamp);

        $this->priceListToWebsiteRepo->expects($this->once())
            ->method('getWebsiteIteratorWithSelfFallback')
            ->willReturn([$website]);
        $this->websiteCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $forceTimestamp);

        $this->priceListToCustomerGroupRepo->expects($this->once())
            ->method('getAllWebsiteIds')
            ->willReturn([$websiteId]);
        $this->priceListToCustomerGroupRepo->expects($this->once())
            ->method('getCustomerGroupIteratorWithSelfFallback')
            ->willReturn([$customerGroup]);
        $this->customerGroupCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $customerGroup, $forceTimestamp);

        $this->priceListToCustomerRepo->expects($this->once())
            ->method('getAllCustomerWebsitePairsWithSelfFallback')
            ->willReturn([new CustomerWebsiteDTO($customer, $website)]);

        $this->customerCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $customer);

        $this->garbageCollector->expects($this->once())
            ->method('cleanCombinedPriceLists');

        $this->facade->rebuildAll($forceTimestamp);
    }

    public function testRebuildForWebsites()
    {
        $website1 = new Website();
        $website2 = new Website();
        $website3 = new Website();

        $this->websiteCombinedPriceListBuilder->expects($this->exactly(3))
            ->method('build')
            ->with($this->logicalOr($website1, $website2, $website3));

        $this->garbageCollector->expects($this->once())
            ->method('cleanCombinedPriceLists');

        $this->facade->rebuildForWebsites([$website1, $website2, $website3]);
    }

    public function testRebuildForCustomerGroups()
    {
        $forceTimestamp = time();
        $website = new Website();
        $customerGroup1 = new CustomerGroup();
        $customerGroup2 = new CustomerGroup();
        $customerGroup3 = new CustomerGroup();

        $this->customerGroupCombinedPriceListBuilder->expects($this->at(0))
            ->method('build')
            ->with($website, $customerGroup1, $forceTimestamp);

        $this->customerGroupCombinedPriceListBuilder->expects($this->at(1))
            ->method('build')
            ->with($website, $customerGroup2, $forceTimestamp);

        $this->customerGroupCombinedPriceListBuilder->expects($this->at(2))
            ->method('build')
            ->with($website, $customerGroup3, $forceTimestamp);

        $this->garbageCollector->expects($this->once())
            ->method('cleanCombinedPriceLists');

        $groups = [$customerGroup1, $customerGroup2, $customerGroup3];
        $this->facade->rebuildForCustomerGroups($groups, $website, $forceTimestamp);
    }

    public function testRebuildForCustomers()
    {
        $forceTimestamp = time();
        $website = new Website();
        $customer1 = new Customer();
        $customer2 = new Customer();
        $customer3 = new Customer();

        $this->customerCombinedPriceListBuilder->expects($this->at(0))
            ->method('build')
            ->with($website, $customer1, $forceTimestamp);

        $this->customerCombinedPriceListBuilder->expects($this->at(1))
            ->method('build')
            ->with($website, $customer2, $forceTimestamp);

        $this->customerCombinedPriceListBuilder->expects($this->at(2))
            ->method('build')
            ->with($website, $customer3, $forceTimestamp);

        $this->garbageCollector->expects($this->once())
            ->method('cleanCombinedPriceLists');

        $customers = [$customer1, $customer2, $customer3];
        $this->facade->rebuildForCustomers($customers, $website, $forceTimestamp);
    }

    public function testRebuildForPriceLists()
    {
        $forceTimestamp = time();

        $websiteId = 11;
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);

        $customerGroupId = 22;
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => $customerGroupId]);

        $customerId = 33;
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => $customerId]);

        /** @var PriceList $priceList1 */
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        /** @var PriceList $priceList3 */
        $priceList3 = $this->getEntity(PriceList::class, ['id' => 3]);
        $priceLists = [$priceList1, $priceList2, $priceList3];

        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [PriceListToWebsite::class, $this->priceListToWebsiteRepo],
                [PriceListToCustomerGroup::class, $this->priceListToCustomerGroupRepo],
                [PriceListToCustomer::class, $this->priceListToCustomerRepo],
            ]);
        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getEntityReference')
            ->willReturnMap([
                [Website::class, $websiteId, $website],
                [CustomerGroup::class, $customerGroupId, $customerGroup],
                [Customer::class, $customerId, $customer]
            ]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.default_price_lists')
            ->willReturn([
                [
                    'priceList' => $priceList1->getId(),
                    'merge' => true
                ]
            ]);
        $this->combinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($forceTimestamp);

        $this->priceListToWebsiteRepo->expects($this->once())
            ->method('getIteratorByPriceLists')
            ->with($priceLists)
            ->willReturn([
                [
                    'website' => $websiteId
                ]
            ]);

        $this->websiteCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $forceTimestamp);

        $this->priceListToCustomerGroupRepo->expects($this->once())
            ->method('getIteratorByPriceLists')
            ->with($priceLists)
            ->willReturn([
                [
                    'website'       => $websiteId,
                    'customerGroup' => $customerGroupId,
                ]
            ]);
        $this->customerGroupCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $customerGroup, $forceTimestamp);

        $this->priceListToCustomerRepo->expects($this->once())
            ->method('getIteratorByPriceLists')
            ->with($priceLists)
            ->willReturn([
                [
                    'website'  => $websiteId,
                    'customer' => $customerId
                ]
            ]);
        $this->customerCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $customer, $forceTimestamp);

        $this->garbageCollector->expects($this->once())
            ->method('cleanCombinedPriceLists');

        $this->facade->rebuildForPriceLists($priceLists, $forceTimestamp);
    }

    public function testDispatchEvents()
    {
        $websiteId = 1;
        $customerIds = [1, 2, 3];
        $customerGroupIds = [11, 12, 13];

        $this->customerCombinedPriceListBuilder->expects($this->once())
            ->method('getBuiltList')
            ->willReturn(['customer' => [$websiteId => $customerIds]]);

        $this->customerGroupCombinedPriceListBuilder->expects($this->once())
            ->method('getBuiltList')
            ->willReturn([$websiteId => $customerGroupIds]);

        $this->websiteCombinedPriceListBuilder->expects($this->once())
            ->method('getBuiltList')
            ->willReturn([$websiteId]);

        $this->combinedPriceListBuilder->expects($this->once())
            ->method('isBuilt')
            ->willReturn(true);

        $this->dispatcher->expects($this->exactly(4))
            ->method('dispatch');

        $this->customerGroupCombinedPriceListBuilder->expects($this->once())
            ->method('resetCache');

        $this->facade->dispatchEvents();
    }
}
