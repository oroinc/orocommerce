<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\PricingBundle\Command\CombinedPriceListScheduleCommand;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsSimplified;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListSchedulesSimplified;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CombinedPriceListScheduleCommandTest extends WebTestCase
{
    use MessageQueueAssertTrait;
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');

        self::getConfigManager('global')
            ->set('oro_pricing.price_strategy', MinimalPricesCombiningStrategy::NAME);
    }

    /**
     * @dataProvider activeDataProvider
     * @param bool $pricesCalculated
     */
    public function testIsActiveNoSchedules($pricesCalculated)
    {
        $this->loadFixtures(
            [
                LoadProductPrices::class,
                LoadCombinedPriceListsSimplified::class
            ]
        );
        $this->buildActivationPlans();
        $this->prepareMessageCollector();

        $this->updatePricesCalculatedForAllCpls($pricesCalculated);

        $command = self::getContainer()->get('oro_pricing.tests.combined_price_list_schedule_command');
        $this->assertFalse($command->isActive());
    }

    /**
     * @dataProvider activeDataProvider
     * @param bool $pricesCalculated
     */
    public function testIsActive($pricesCalculated)
    {
        $this->loadFixturesWithSchedules();
        $this->updatePricesCalculatedForAllCpls($pricesCalculated);

        $command = self::getContainer()->get('oro_pricing.tests.combined_price_list_schedule_command');
        $this->assertTrue($command->isActive());
    }

    public function testIsActiveWithAllActualSchedules()
    {
        $this->loadFixturesWithSchedules();
        $this->updateActivationPlanActivity(true);

        $command = self::getContainer()->get('oro_pricing.tests.combined_price_list_schedule_command');
        $this->assertFalse($command->isActive());
    }

    public function activeDataProvider(): array
    {
        return [
            'calculated' => [true],
            'not calculated' => [false]
        ];
    }

    public function testCommand()
    {
        $this->loadFixturesWithSchedules();

        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $customerGroup = $this->getReference(LoadGroups::GROUP1);

        $this->assertCustomerGroupActiveCPL($website, $customerGroup, '1_2_3', false);

        $this->runCommand(CombinedPriceListScheduleCommand::getDefaultName());

        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $priceList3 = $this->getReference(LoadPriceLists::PRICE_LIST_3);
        $expectedPriceListName = sprintf('%d_%d', $priceList2->getId(), $priceList3->getId());
        $this->assertCustomerGroupActiveCPL($website, $customerGroup, $expectedPriceListName, true);

        $this->assertMessageCollectorContainsRightMessagesOnReindex();
    }

    private function assertMessageCollectorContainsRightMessagesOnReindex()
    {
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            [
                'class' => [Product::class],
                'context' => [
                    'entityIds' => [
                        $this->getReference(LoadProductData::PRODUCT_1)->getId(),
                        $this->getReference(LoadProductData::PRODUCT_2)->getId(),
                        $this->getReference(LoadProductData::PRODUCT_3)->getId()
                    ],
                    'websiteIds' => $this->getWebsiteIds()
                ],
                'granulize' => true,
            ]
        );
    }

    private function getWebsiteIds(): array
    {
        /** @var WebsiteProviderInterface $websiteProvider */
        $websiteProvider = $this->getContainer()->get('oro_website.website.provider');

        return $websiteProvider->getWebsiteIds();
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     * @param string $expectedActivePriceList
     */
    protected function assertCustomerGroupActiveCPL(
        Website $website,
        CustomerGroup $customerGroup,
        string $expectedActivePriceList,
        bool $isPricesCalculated
    ) {
        /** @var CombinedPriceListToCustomerGroupRepository $cplToCustomerGroupRepo */
        $cplToCustomerGroupRepo = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CombinedPriceListToCustomerGroup::class)
            ->getRepository(CombinedPriceListToCustomerGroup::class);

        /** @var CombinedPriceListToCustomerGroup $groupToCPL */
        $groupToCPL = $cplToCustomerGroupRepo->findOneBy([
            'website' => $website,
            'customerGroup' => $customerGroup
        ]);

        $this->getContainer()->get('doctrine')
            ->getManagerForClass(CombinedPriceListToCustomerGroup::class)
            ->refresh($groupToCPL);

        $this->assertEquals(
            md5($expectedActivePriceList),
            $groupToCPL->getPriceList()->getName(),
            'Active CPL should be ' . $expectedActivePriceList
        );
        $this->assertEquals($isPricesCalculated, $groupToCPL->getPriceList()->isPricesCalculated());
    }

    protected function buildActivationPlans()
    {
        $activationPlanBuilder = $this->getContainer()
            ->get('oro_pricing.builder.combined_price_list_activation_plan_builder');

        $activationPlanBuilder->buildByPriceList($this->getReference(LoadPriceLists::PRICE_LIST_1));
        $activationPlanBuilder->buildByPriceList($this->getReference(LoadPriceLists::PRICE_LIST_2));
    }

    protected function prepareMessageCollector()
    {
        $this->clearMessageCollector();
        $this->assertCount(0, $this->getSentMessages());
    }

    protected function loadFixturesWithSchedules(): void
    {
        $this->loadFixtures(
            [
                LoadProductPrices::class,
                LoadCombinedPriceListsSimplified::class,
                LoadPriceListSchedulesSimplified::class
            ]
        );
        $this->buildActivationPlans();
        $this->prepareMessageCollector();
    }

    /**
     * @param bool $pricesCalculated
     */
    protected function updatePricesCalculatedForAllCpls($pricesCalculated): void
    {
        self::getContainer()->get('doctrine')
            ->getManagerForClass(CombinedPriceList::class)
            ->createQueryBuilder()
            ->update(CombinedPriceList::class, 'cpl')
            ->set('cpl.pricesCalculated', ':pricesCalculated')
            ->setParameter('pricesCalculated', $pricesCalculated)
            ->getQuery()
            ->execute();
    }

    /**
     * @param bool $isActive
     */
    protected function updateActivationPlanActivity($isActive): void
    {
        self::getContainer()->get('doctrine')
            ->getManagerForClass(CombinedPriceListActivationRule::class)
            ->createQueryBuilder()
            ->update(CombinedPriceListActivationRule::class, 'rule')
            ->set('rule.active', ':active')
            ->setParameter('active', $isActive)
            ->getQuery()
            ->execute();
    }
}
