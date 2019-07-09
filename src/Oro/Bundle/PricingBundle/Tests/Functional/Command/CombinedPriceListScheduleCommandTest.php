<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\PricingBundle\Command\CombinedPriceListScheduleCommand;
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
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Component\MessageQueue\Client\Message;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CombinedPriceListScheduleCommandTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        self::getContainer()->get('oro_config.global')
            ->set('oro_pricing.price_strategy', MinimalPricesCombiningStrategy::NAME);

        $this->loadFixtures(
            [
                LoadPriceListSchedulesSimplified::class,
                LoadProductPrices::class,
                LoadCombinedPriceListsSimplified::class
            ]
        );
        $this->buildActivationPlans();
        $this->prepareMessageCollector();
    }

    public function testCommand()
    {
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $customerGroup = $this->getReference(LoadGroups::GROUP1);

        $this->assertCustomerGroupActiveCPL($website, $customerGroup, '1t_3t_2t');

        $this->runCommand(CombinedPriceListScheduleCommand::getDefaultName());

        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $priceList3 = $this->getReference(LoadPriceLists::PRICE_LIST_3);
        $expectedPriceListName = sprintf('%dt_%dt', $priceList3->getId(), $priceList2->getId());
        $this->assertCustomerGroupActiveCPL($website, $customerGroup, $expectedPriceListName);

        $this->assertMessageCollectorContainsRightMessagesOnReindex();
    }

    private function assertMessageCollectorContainsRightMessagesOnReindex()
    {
        $reindexMessages = $this->getMessageCollector()->getTopicSentMessages(AsyncIndexer::TOPIC_REINDEX);
        $this->assertCount(1, $reindexMessages);

        /** @var Message $reindexMessage */
        $reindexMessage = reset($reindexMessages)['message'];

        $this->assertEquals(
            [
                'class' => [Product::class],
                'context' => [
                    'entityIds' => [
                        $this->getReference(LoadProductData::PRODUCT_1)->getId(),
                        $this->getReference(LoadProductData::PRODUCT_2)->getId()
                    ],
                ],
                'granulize' => true,
            ],
            $reindexMessage->getBody()
        );
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     * @param string $expectedActivePriceList
     */
    protected function assertCustomerGroupActiveCPL(
        Website $website,
        CustomerGroup $customerGroup,
        $expectedActivePriceList
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
        $this->assertTrue($groupToCPL->getPriceList()->isPricesCalculated());
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
        $this->getMessageCollector()->clear();
        $this->assertCount(0, $this->getSentMessages());
    }
}
