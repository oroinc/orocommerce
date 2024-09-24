<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Debug\Provider\CombinedPriceListActivationRulesProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombinedPriceListActivationRulesProviderTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $registry;
    private ConfigManager|MockObject $configManager;
    private CustomerUserRelationsProvider|MockObject $customerUserRelationsProvider;
    private CombinedPriceListActivationRulesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);

        $this->provider = new CombinedPriceListActivationRulesProvider(
            $this->registry,
            $this->configManager,
            $this->customerUserRelationsProvider
        );
    }

    public function testGetActivationRules()
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $rules = [
            $this->getEntity(CombinedPriceListActivationRule::class, ['id' => 10])
        ];

        $repo = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['fullChainPriceList' => $cpl], ['expireAt' => 'ASC'])
            ->willReturn($rules);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListActivationRule::class)
            ->willReturn($repo);

        $this->assertSame($rules, $this->provider->getActivationRules($cpl));
    }

    public function testHasActivationRules()
    {
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $repo = $this->createMock(CombinedPriceListActivationRuleRepository::class);
        $repo->expects($this->once())
            ->method('hasActivationRules')
            ->with($cpl)
            ->willReturn(true);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListActivationRule::class)
            ->willReturn($repo);

        $this->assertTrue($this->provider->hasActivationRules($cpl));
    }

    public function testGetFullChainCplNoWebsite()
    {
        $id = 1;
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => $id]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.full_combined_price_list')
            ->willReturn($id);

        $repo = $this->createMock(CombinedPriceListRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($cpl);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceList::class)
            ->willReturn($repo);

        $this->assertSame($cpl, $this->provider->getFullChainCpl());
    }

    public function testGetFullChainCplNoCustomer()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 2]);
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->customerUserRelationsProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->willReturn($customerGroup);

        $relation = new CombinedPriceListToCustomerGroup();
        $relation->setFullChainPriceList($cpl);
        $repo = $this->createMock(CombinedPriceListToCustomerGroupRepository::class);
        $repo->expects($this->once())
            ->method('getRelation')
            ->with($website, $customerGroup)
            ->willReturn($relation);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListToCustomerGroup::class)
            ->willReturn($repo);

        $this->assertSame($cpl, $this->provider->getFullChainCpl(null, $website));
    }

    public function testGetFullChainCplWithCustomerWithoutGroupNoCustomerRelation()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customer = $this->getEntity(Customer::class, ['id' => 3]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 2]);
        $customer->setGroup($customerGroup);
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->customerUserRelationsProvider->expects($this->never())
            ->method('getCustomerGroup');

        $relation = new CombinedPriceListToCustomerGroup();
        $relation->setFullChainPriceList($cpl);

        $websiteRepo = $this->createMock(CombinedPriceListToWebsiteRepository::class);
        $websiteRepo->expects($this->never())
            ->method('getRelation');

        $customerRepo = $this->createMock(CombinedPriceListToCustomerRepository::class);
        $customerRepo->expects($this->once())
            ->method('getRelation')
            ->with($website, $customer)
            ->willReturn(null);

        $groupRepo = $this->createMock(CombinedPriceListToCustomerGroupRepository::class);
        $groupRepo->expects($this->once())
            ->method('getRelation')
            ->with($website, $customerGroup)
            ->willReturn($relation);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with()
            ->willReturnMap([
                [CombinedPriceListToCustomer::class, null, $customerRepo],
                [CombinedPriceListToCustomerGroup::class, null, $groupRepo],
                [CombinedPriceListToWebsite::class, null, $websiteRepo]
            ]);

        $this->assertSame($cpl, $this->provider->getFullChainCpl($customer, $website));
    }

    public function testGetFullChainCplWithCustomerWithoutGroupWithCustomerRelation()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customer = $this->getEntity(Customer::class, ['id' => 3]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 2]);
        $customer->setGroup($customerGroup);
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->customerUserRelationsProvider->expects($this->never())
            ->method('getCustomerGroup');

        $relation = new CombinedPriceListToCustomer();
        $relation->setFullChainPriceList($cpl);

        $websiteRepo = $this->createMock(CombinedPriceListToWebsiteRepository::class);
        $websiteRepo->expects($this->never())
            ->method('getRelation');

        $customerRepo = $this->createMock(CombinedPriceListToCustomerRepository::class);
        $customerRepo->expects($this->once())
            ->method('getRelation')
            ->with($website, $customer)
            ->willReturn($relation);

        $groupRepo = $this->createMock(CombinedPriceListToCustomerGroupRepository::class);
        $groupRepo->expects($this->never())
            ->method('getRelation');

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with()
            ->willReturnMap([
                [CombinedPriceListToCustomer::class, null, $customerRepo],
                [CombinedPriceListToCustomerGroup::class, null, $groupRepo],
                [CombinedPriceListToWebsite::class, null, $websiteRepo]
            ]);

        $this->assertSame($cpl, $this->provider->getFullChainCpl($customer, $website));
    }

    public function testGetFullChainCplWithCustomerWithGroup()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customer = $this->getEntity(Customer::class, ['id' => 3]);
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->configManager->expects($this->never())
            ->method('get');

        $this->customerUserRelationsProvider->expects($this->never())
            ->method('getCustomerGroup');

        $relation = new CombinedPriceListToWebsite();
        $relation->setFullChainPriceList($cpl);

        $websiteRepo = $this->createMock(CombinedPriceListToWebsiteRepository::class);
        $websiteRepo->expects($this->once())
            ->method('getRelation')
            ->with($website)
            ->willReturn($relation);

        $customerRepo = $this->createMock(CombinedPriceListToCustomerRepository::class);
        $customerRepo->expects($this->once())
            ->method('getRelation')
            ->with($website, $customer)
            ->willReturn(null);

        $groupRepo = $this->createMock(CombinedPriceListToCustomerGroupRepository::class);
        $groupRepo->expects($this->never())
            ->method('getRelation');

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with()
            ->willReturnMap([
                [CombinedPriceListToCustomer::class, null, $customerRepo],
                [CombinedPriceListToCustomerGroup::class, null, $groupRepo],
                [CombinedPriceListToWebsite::class, null, $websiteRepo]
            ]);

        $this->assertSame($cpl, $this->provider->getFullChainCpl($customer, $website));
    }

    public function testGetFullChainCplWithCustomerWithoutGroupFallbackToConfig()
    {
        $id = 1;
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customer = $this->getEntity(Customer::class, ['id' => 3]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 2]);
        $customer->setGroup($customerGroup);
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->customerUserRelationsProvider->expects($this->never())
            ->method('getCustomerGroup');

        $websiteRepo = $this->createMock(CombinedPriceListToWebsiteRepository::class);
        $websiteRepo->expects($this->once())
            ->method('getRelation')
            ->with($website)
            ->willReturn(null);

        $customerRepo = $this->createMock(CombinedPriceListToCustomerRepository::class);
        $customerRepo->expects($this->once())
            ->method('getRelation')
            ->with($website, $customer)
            ->willReturn(null);

        $groupRepo = $this->createMock(CombinedPriceListToCustomerGroupRepository::class);
        $groupRepo->expects($this->once())
            ->method('getRelation')
            ->with($website, $customerGroup)
            ->willReturn(null);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.full_combined_price_list')
            ->willReturn($id);

        $cplRepo = $this->createMock(CombinedPriceListRepository::class);
        $cplRepo->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($cpl);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with()
            ->willReturnMap([
                [CombinedPriceListToCustomer::class, null, $customerRepo],
                [CombinedPriceListToCustomerGroup::class, null, $groupRepo],
                [CombinedPriceListToWebsite::class, null, $websiteRepo],
                [CombinedPriceList::class, null, $cplRepo]
            ]);

        $this->assertSame($cpl, $this->provider->getFullChainCpl($customer, $website));
    }

    public function testGetFullChainCplForWebsite()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customer = null;
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $this->configManager->expects($this->never())
            ->method('get');

        $relation = new CombinedPriceListToWebsite();
        $relation->setFullChainPriceList($cpl);

        $websiteRepo = $this->createMock(CombinedPriceListToWebsiteRepository::class);
        $websiteRepo->expects($this->once())
            ->method('getRelation')
            ->with($website)
            ->willReturn($relation);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with()
            ->willReturnMap([
                [CombinedPriceListToWebsite::class, null, $websiteRepo]
            ]);

        $this->assertSame($cpl, $this->provider->getFullChainCpl($customer, $website));
    }
}
