<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Builder;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
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
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CustomerCombinedPriceListsBuilder */
    protected $customerCombinedPriceListBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CustomerGroupCombinedPriceListsBuilder */
    protected $customerGroupCombinedPriceListBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WebsiteCombinedPriceListsBuilder */
    protected $websiteCombinedPriceListBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CombinedPriceListsBuilder */
    protected $combinedPriceListBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    protected $dispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|StrategyRegister */
    protected $strategyRegister;

    /** @var CombinedPriceListsBuilderFacade */
    protected $facade;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PriceListToWebsiteRepository */
    protected $priceListToWebsiteRepo;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PriceListToCustomerGroupRepository */
    protected $priceListToCustomerGroupRepo;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PriceListToCustomerRepository */
    protected $priceListToCustomerRepo;

    public function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->customerCombinedPriceListBuilder = $this->createMock(CustomerCombinedPriceListsBuilder::class);
        $this->customerGroupCombinedPriceListBuilder = $this->createMock(CustomerGroupCombinedPriceListsBuilder::class);
        $this->websiteCombinedPriceListBuilder = $this->createMock(WebsiteCombinedPriceListsBuilder::class);
        $this->combinedPriceListBuilder = $this->createMock(CombinedPriceListsBuilder::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->strategyRegister = $this->createMock(StrategyRegister::class);

        $this->facade = new CombinedPriceListsBuilderFacade(
            $this->doctrineHelper,
            $this->customerCombinedPriceListBuilder,
            $this->customerGroupCombinedPriceListBuilder,
            $this->websiteCombinedPriceListBuilder,
            $this->combinedPriceListBuilder,
            $this->dispatcher,
            $this->strategyRegister
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
        $this->strategyRegister
            ->expects($this->once())
            ->method('getCurrentStrategy')
            ->willReturn($strategy);

        $strategy
            ->expects($this->exactly(3))
            ->method('combinePrices')
            ->withConsecutive(
                [$combinedPriceList1, $products, $startTimestamp],
                [$combinedPriceList2, $products, $startTimestamp],
                [$combinedPriceList3, $products, $startTimestamp]
            );

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CombinedPriceListsUpdateEvent::NAME, new CombinedPriceListsUpdateEvent([11, 22, 33]));

        $this->facade->rebuild($combinedPriceLists, $products, $startTimestamp);
        $this->facade->dispatchEvents();
    }

    public function testRebuildAll()
    {
        $forceTimestamp = time();
        $website = new Website();
        $websiteId = 1;

        $this->combinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($forceTimestamp);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepositoryForClass')
            ->will($this->returnValueMap([
                [PriceListToCustomerGroup::class, $this->priceListToCustomerGroupRepo],
                [PriceListToCustomer::class, $this->priceListToCustomerRepo],
            ]));

        $this->priceListToCustomerGroupRepo->expects($this->once())
            ->method('getAllWebsiteIds')
            ->willReturn([$websiteId]);

        $this->doctrineHelper->expects($this->once())->method('getEntityReference')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        $this->customerGroupCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, null, $forceTimestamp);

        $customer = new Customer();

        $this->priceListToCustomerRepo->expects($this->once())
            ->method('getAllCustomerWebsitePairs')
            ->willReturn([new CustomerWebsiteDTO($customer, $website)]);

        $this->customerCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $customer);

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

        $customers = [$customer1, $customer2, $customer3];
        $this->facade->rebuildForCustomers($customers, $website, $forceTimestamp);
    }

    public function testRebuildForPriceLists()
    {
        $forceTimestamp = time();
        $website = new Website();
        $websiteId = 11;
        $customerGroup = new CustomerGroup();
        $customerGroupId = 22;
        $customer = new Customer();
        $customerId = 33;
        $priceList1 = new PriceList();
        $priceList2 = new PriceList();
        $priceList3 = new PriceList();
        $priceLists = [$priceList1, $priceList2, $priceList3];

        $this->doctrineHelper->expects($this->exactly(3))
            ->method('getEntityRepositoryForClass')
            ->will($this->returnValueMap([
                [PriceListToWebsite::class, $this->priceListToWebsiteRepo],
                [PriceListToCustomerGroup::class, $this->priceListToCustomerGroupRepo],
                [PriceListToCustomer::class, $this->priceListToCustomerRepo],
            ]));

        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getEntityReference')
            ->will($this->returnValueMap([
                [Website::class, $websiteId, $website],
                [CustomerGroup::class, $customerGroupId, $customerGroup],
                [Customer::class, $customerId, $customer]
            ]));

        $this->priceListToWebsiteRepo->expects($this->once())
            ->method('getIteratorByPriceLists')
            ->with($priceLists)
            ->willReturn([[
                PriceListRelationTrigger::WEBSITE => $websiteId
            ]]);

        $this->websiteCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $forceTimestamp);

        $this->priceListToCustomerGroupRepo->expects($this->once())
            ->method('getIteratorByPriceLists')
            ->with($priceLists)
            ->willReturn([[
                PriceListRelationTrigger::WEBSITE => $websiteId,
                PriceListRelationTrigger::ACCOUNT_GROUP => $customerGroupId,
            ]]);
        $this->customerGroupCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $customerGroup, $forceTimestamp);

        $this->priceListToCustomerRepo->expects($this->once())
            ->method('getIteratorByPriceLists')
            ->with($priceLists)
            ->willReturn([[
                PriceListRelationTrigger::WEBSITE => $websiteId,
                PriceListRelationTrigger::ACCOUNT => $customerId,
            ]]);
        $this->customerCombinedPriceListBuilder->expects($this->once())
            ->method('build')
            ->with($website, $customer, $forceTimestamp);

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

        $this->dispatcher->expects($this->exactly(4))->method('dispatch');

        $this->customerGroupCombinedPriceListBuilder->expects($this->once())->method('resetCache');

        $this->facade->dispatchEvents();
    }
}
