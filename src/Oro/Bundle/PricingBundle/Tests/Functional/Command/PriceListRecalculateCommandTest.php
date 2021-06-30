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
            $this->assertCount($count, $this->getSentMessagesByTopic($topic));
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
                'expectedCount' => 64, // 2 + 52 + 8 + 2 = config + all levels for website1, website2 & website3
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
            ],
            'all' => [
                'expected_message' => 'Start processing',
                'params' => ['--all'],
                'expectedCount' => 64, // 2 + 52 + 8 + 2 = config + all levels for website1, website2 & website3
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
            ],
            'all with triggers off with insert-select' => [
                'expected_message' => 'Start processing',
                'params' => ['--all', '--disable-triggers', '--use-insert-select'],
                'expectedCount' => 64, // 2 + 52 + 8 + 2 = config + all levels for website1, website2 & website3
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],
            ],
            'all with insert-select' => [
                'expected_message' => 'Start processing',
                'params' => ['--all', '--use-insert-select'],
                'expectedCount' => 64, // 2 + 52 + 8 + 2 = config + all levels for website1, website2 & website3
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
                'expectedCount' => 48, // 10 + 10 + 14 + 14 = website1 + group1 + customer_1.3 + customer_1_1
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
                'expectedCount' => 22,  // 14 + 8 = customer.level_1_1 + website2
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
                'expectedCount' => 14,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],                'website' => [],
                'customerGroup' => [],
                'customer' => ['customer.level_1.3']
            ],
            'customer_group' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 24, // 6 + 4 + 14 = customer.level_1_1 + customer.level_1.2 + customer.level_1.3
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],                'website' => [],
                'customerGroup' => ['customer_group.group1'], // doesn't has own price list
                'customer' => []
            ],
            'price_list_1' => [
                'expected_message' => 'Start the process',
                'params' => [],
                // 10 + 10 + 14 + 14 + 8 = WS(US) + group1 + customer_1.3(US) + customer_1_1(US) + customer_1_1(US)
                'expectedCount' => 56,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],                'website' => [],
                'customerGroup' => [],
                'customer' => [],
                'priceLists'=> [LoadPriceLists::PRICE_LIST_1]
            ],
            'price_list_1 with dependant' => [
                'expected_message' => 'Start the process',
                'params' => ['--include-dependent'],
                // 10 + 2 + 10 + 14 + 14 + 8 + 2
                // WS1(US) + WS3(CA) + group1 + customer_1.3(WS1:US) + customer_1_1(WS1:US) + customer_1_1(WS2:Canada)
                'expectedCount' => 58,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],                'website' => [],
                'customerGroup' => [],
                'customer' => [],
                'priceLists'=> [LoadPriceLists::PRICE_LIST_1]
            ],
            'verbosity_verbose' => [
                'expected_message' => 'Processing combined price list id:',
                'params' => ['--all', '-v'],
                'expectedCount' => 64,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],            ],
            'verbosity_very_verbose' => [
                'expected_message' => 'Processing price list:',
                'params' => ['--all', '-vv'],
                'expectedCount' => 64,
                'expectedMessages' => [
                    'oro.checkout.recalculate_checkout_subtotals' => 1,
                    'oro.website.search.indexer.reindex' => 1
                ],            ],
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
