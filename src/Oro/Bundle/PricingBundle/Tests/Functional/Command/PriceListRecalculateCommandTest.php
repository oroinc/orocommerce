<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityBundle\Manager\Db\EntityTriggerManager;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\PricingBundle\Command\PriceListRecalculateCommand;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\PricingStrategy\MinimalPricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadDependentPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadDependentPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Unit\Entity\Repository\Stub\CombinedProductPriceRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class PriceListRecalculateCommandTest extends WebTestCase
{
    use MessageQueueAssertTrait;
    use ConfigManagerAwareTestTrait;

    /** @var EntityTriggerManager|\PHPUnit\Framework\MockObject\MockObject */
    private $databaseTriggerManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        self::getConfigManager('global')
            ->set('oro_pricing.price_strategy', MinimalPricesCombiningStrategy::NAME);

        $this->databaseTriggerManager = $this->createMock(EntityTriggerManager::class);
        $this->getContainer()->set(
            'oro_pricing.tests.database_triggers.manager.combined_prices',
            $this->databaseTriggerManager
        );

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
     * @param $expectedMessage
     * @param array $params
     * @param int $expectedCount
     * @param array $expectedMesssages
     * @param array $websites
     * @param array $customerGroups
     * @param array $customers
     * @param array $priceLists
     */
    public function testCommand(
        $expectedMessage,
        array $params,
        $expectedCount,
        array $expectedMesssages,
        array $websites = [],
        array $customerGroups = [],
        array $customers = [],
        array $priceLists = []
    ) {
        $this->clearCombinedPrices();
        $this->assertCombinedPriceCount(0);

        $this->clearMessageCollector();
        $this->assertCount(0, $this->getSentMessages());

        $this->getContainer()->get('oro_pricing.builder.combined_price_list_builder')->resetCache();
        $this->getContainer()->get('oro_pricing.builder.website_combined_price_list_builder')->resetCache();
        $this->getContainer()->get('oro_pricing.builder.customer_group_combined_price_list_builder')->resetCache();
        $this->getContainer()->get('oro_pricing.builder.customer_combined_price_list_builder')->resetCache();
        $this->getContainer()->get('oro_pricing.pricing_strategy.strategy_register')
            ->getCurrentStrategy()
            ->resetCache();

        foreach ($websites as $websiteName) {
            $params[] = '--website='.$this->getReference($websiteName)->getId();
        }

        foreach ($customerGroups as $customerGroupName) {
            $params[] = '--customer-group='.$this->getReference($customerGroupName)->getId();
        }

        foreach ($customers as $customerName) {
            $params[] = '--customer='.$this->getReference($customerName)->getId();
        }

        foreach ($priceLists as $priceListName) {
            $params[] = '--price-list='.$this->getReference($priceListName)->getId();
        }

        if (\in_array('--disable-triggers', $params, true)) {
            $databasePlatform = $this->getContainer()->get('doctrine')->getConnection()->getDatabasePlatform();
            if ($databasePlatform instanceof MySqlPlatform) {
                $expectedMessage = sprintf(
                    'The option `disable-triggers` is not available for `%s` database',
                    $databasePlatform->getName()
                );
                $expectedCount = 0;
                $expectedMesssages = [];
            } else {
                $this->databaseTriggerManager->expects($this->once())
                    ->method('disable');
                $this->databaseTriggerManager->expects($this->once())
                    ->method('enable');
            }
        } else {
            $this->databaseTriggerManager->expects($this->never())
                ->method('enable');
            $this->databaseTriggerManager->expects($this->never())
                ->method('disable');
        }

        $result = $this->runCommand(PriceListRecalculateCommand::getDefaultName(), $params);
        $this->assertStringContainsString($expectedMessage, $result);
        $this->assertCombinedPriceCount($expectedCount);
        $this->assertCount(array_sum($expectedMesssages), $this->getSentMessages());
        foreach ($expectedMesssages as $topic => $count) {
            self::assertMessagesCount($topic, $count);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function commandDataProvider()
    {
        return [
            'all with triggers off' => [
                'expected_message' => 'Start processing',
                'params' => ['--all', '--disable-triggers'],
                // 2 - config,
                // 11 - US website, 1 - CA website,
                // 8 - customer.level_1_1, 4 - customer.level_1.2, 15 - customer.level_1.3
                'expectedCount' => 41,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
            ],
            'all' => [
                'expected_message' => 'Start processing',
                'params' => ['--all'],
                // 2 - config,
                // 11 - US website, 1 - CA website,
                // 8 - customer.level_1_1, 4 - customer.level_1.2, 15 - customer.level_1.3
                'expectedCount' => 41,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
            ],
            'all with triggers off with insert-select' => [
                'expected_message' => 'Start processing',
                'params' => ['--all', '--disable-triggers', '--use-insert-select'],
                // 2 - config,
                // 11 - US website, 1 - CA website,
                // 8 - customer.level_1_1, 4 - customer.level_1.2, 15 - customer.level_1.3
                'expectedCount' => 41,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
            ],
            'all with insert-select' => [
                'expected_message' => 'Start processing',
                'params' => ['--all', '--use-insert-select'],
                // 2 - config,
                // 11 - US website, 1 - CA website,
                // 8 - customer.level_1_1, 4 - customer.level_1.2, 15 - customer.level_1.3
                'expectedCount' => 41,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
            ],
            'empty run' => [
                'expected_message' => 'ATTENTION',
                'params' => [],
                'expectedCount' => 0,
                'expectedMessages' => [],
            ],
            'website 1' => [
                'expected_message' => 'Start processing',
                'params' => [],
                // 11 - US website
                // 15 - customer.level_1.3
                'expectedCount' => 26,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
                'website' => [LoadWebsiteData::WEBSITE1],
                'customerGroup' => [],
                'customer' => []
            ],
            'customer.level_1_1' => [
                'expected_message' => 'Start processing',
                'params' => [],
                // 15 - customer.level_1_1, 8  - customer.level_1_1 (CA website)
                'expectedCount' => 23,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1_1']
            ],
            'customer.level_1.2' => [
                'expected_message' => 'Start processing',
                'params' => [],
                // 4 - customer.level_1.2
                'expectedCount' => 4,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1.2']
            ],
            'customer.level_1.3' => [
                'expected_message' => 'Start processing',
                'params' => [],
                // 15 - customer.level_1.3
                'expectedCount' => 15,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1.3']
            ],
            'customer_group' => [
                'expected_message' => 'Start processing',
                'params' => [],
                // 11 - customer_group.group1
                // 15 - customer.level_1.3
                'expectedCount' => 26,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
                'website' => [],
                'customerGroup' => ['customer_group.group1'], // doesn't has own price list
                'customer' => []
            ],
            'price_list_1' => [
                'expected_message' => 'Start the process',
                'params' => [],
                // 11 - US website
                // 15 - customer.level_1.3, 8 - customer.level_1_1
                'expectedCount' => 34,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => [],
                'priceLists' => [LoadPriceLists::PRICE_LIST_1]
            ],
            'price_list_1 with dependant' => [
                'expected_message' => 'Start the process',
                'params' => ['--include-dependent'],
                // 11 - US website, 2 - CA website
                // 8 - customer.level_1_1, 15 - customer.level_1.3
                'expectedCount' => 36,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
                'website' => [],
                'customerGroup' => [],
                'customer' => [],
                'priceLists' => [LoadPriceLists::PRICE_LIST_1]
            ],
            'verbosity_verbose' => [
                'expected_message' => 'Processing combined price list id:',
                'params' => ['--all', '-v'],
                // 2 - config,
                // 11 - US website, 1 - CA website,
                // 8 - customer.level_1_1, 4 - customer.level_1.2, 15 - customer.level_1.3
                'expectedCount' => 41,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
            ],
            'verbosity_very_verbose' => [
                'expected_message' => 'Processing price list:',
                'params' => ['--all', '-vv'],
                // 2 - config,
                // 11 - US website, 1 - CA website,
                // 8 - customer.level_1_1, 4 - customer.level_1.2, 15 - customer.level_1.3
                'expectedCount' => 41,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
            ],
        ];
    }

    /**
     * @param int $expectedCount
     */
    protected function assertCombinedPriceCount($expectedCount)
    {
        /** @var CombinedProductPriceRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(CombinedProductPrice::class);
        $combinedPrices = $repo
            ->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals($expectedCount, $combinedPrices);
    }

    protected function clearCombinedPrices()
    {
        /** @var CombinedProductPriceRepository $repo */
        $repo = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CombinedProductPrice::class)
            ->getRepository(CombinedProductPrice::class);

        $repo->createQueryBuilder('combinedProductPrice')
            ->delete(CombinedProductPrice::class, 'combinedProductPrice')
            ->getQuery()
            ->execute();
    }
}
