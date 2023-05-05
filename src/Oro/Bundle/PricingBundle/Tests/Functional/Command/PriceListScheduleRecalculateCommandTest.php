<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Command\PriceListScheduleRecalculateCommand;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadDependentPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadDependentPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class PriceListScheduleRecalculateCommandTest extends WebTestCase
{
    use MessageQueueAssertTrait;
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        self::getConfigManager()
            ->set('oro_pricing.price_strategy', MinimalPricesCombiningStrategy::NAME);

        $this->loadFixtures([
            LoadPriceListRelations::class,
            LoadProductPrices::class,
            LoadDependentPriceLists::class,
            LoadDependentPriceListRelations::class,
            LoadPriceListFallbackSettings::class,
        ]);
    }

    /**
     * @dataProvider commandDataProvider
     */
    public function testCommand(
        array $expectedConsoleMessages,
        array $params,
        array $expectedMqMessages,
        array $websites = [],
        array $customerGroups = [],
        array $customers = [],
        array $priceLists = []
    ) {
        $this->clearMessageCollector();
        $this->assertCount(0, $this->getSentMessages());

        foreach ($websites as $websiteName) {
            $params[] = '--website=' . $this->getReference($websiteName)->getId();
        }

        foreach ($customerGroups as $customerGroupName) {
            $params[] = '--customer-group=' . $this->getReference($customerGroupName)->getId();
        }

        foreach ($customers as $customerName) {
            $params[] = '--customer=' . $this->getReference($customerName)->getId();
        }

        foreach ($priceLists as $priceListName) {
            $params[] = '--price-list=' . $this->getReference($priceListName)->getId();
        }

        $result = $this->runCommand(PriceListScheduleRecalculateCommand::getDefaultName(), $params);
        foreach ($expectedConsoleMessages as $message) {
            $this->assertStringContainsString($message, $result);
        }

        foreach ($expectedMqMessages as $topic => $count) {
            $messages = $this->getSentMessagesByTopic($topic);
            $this->assertCount(
                $count,
                $messages,
                sprintf('Unexpected messages sent to topic %s. Messages: %s', $topic, json_encode($messages))
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function commandDataProvider(): array
    {
        return [
            'all' => [
                'expected_message' => [
                    'Scheduling all Price Lists combining',
                    'Updates were scheduled'
                ],
                'params' => ['--all'],
                'expectedMessages' => [
                    MassRebuildCombinedPriceListsTopic::getName() => 1
                ],
            ],
            'empty run' => [
                'expected_message' => ['ATTENTION'],
                'params' => [],
                'expectedMessages' => [],
            ],
            'website 1' => [
                'expected_message' => [
                    'Scheduling combining Price Lists for Website ID',
                    'Updates were scheduled'
                ],
                'params' => [],
                'expectedMessages' => [
                    MassRebuildCombinedPriceListsTopic::getName() => 1
                ],
                'website' => [LoadWebsiteData::WEBSITE1],
                'customerGroup' => [],
                'customer' => []
            ],
            'customer.level_1_1' => [
                'expected_message' => [
                    'Scheduling combining Price Lists for Customer ID',
                    'Updates were scheduled'
                ],
                'params' => [],
                'expectedMessages' => [
                    // 1 message for customer per website (there are 4 websites)
                    MassRebuildCombinedPriceListsTopic::getName() => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1_1']
            ],
            'customer.level_1.2' => [
                'expected_message' => [
                    'Scheduling combining Price Lists for Customer ID',
                    'Updates were scheduled'
                ],
                'params' => [],
                'expectedMessages' => [
                    // 1 message for customer per website (there are 4 websites)
                    MassRebuildCombinedPriceListsTopic::getName() => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1.2']
            ],
            'customer.level_1.3' => [
                'expected_message' => [
                    'Scheduling combining Price Lists for Customer ID',
                    'Updates were scheduled'
                ],
                'params' => [],
                'expectedMessages' => [
                    // 1 message for customer per website (there are 4 websites)
                    MassRebuildCombinedPriceListsTopic::getName() => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1.3']
            ],
            'customer_group' => [
                'expected_message' => [
                    'Scheduling combining Price Lists for Customer Group ID',
                    'Updates were scheduled'
                ],
                'params' => [],
                'expectedMessages' => [
                    // 1 message for customer group per website (there are 4 websites)
                    MassRebuildCombinedPriceListsTopic::getName() => 1
                ],
                'website' => [],
                'customerGroup' => ['customer_group.group1'], // doesn't have own price list
                'customer' => []
            ],
            'price_list_1' => [
                'expected_message' => [
                    'Scheduling combining Price Lists by Price List ID',
                    'Updates were scheduled'
                ],
                'params' => [],
                'expectedMessages' => [
                    // PL assigned to website and to website for customer
                    MassRebuildCombinedPriceListsTopic::getName() => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => [],
                'priceLists' => [LoadPriceLists::PRICE_LIST_1]
            ],
            'price_list_1 with dependant' => [
                'expected_message' => [
                    'Scheduling combining Price Lists by Price List ID',
                    'Updates were scheduled'
                ],
                'params' => ['--include-dependent'],
                'expectedMessages' => [
                    // base PL assigned to website and to website for customer
                    // dependent PL assigned to website
                    MassRebuildCombinedPriceListsTopic::getName() => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => [],
                'priceLists' => [LoadPriceLists::PRICE_LIST_1]
            ]
        ];
    }
}
