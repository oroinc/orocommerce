<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Command\PriceListRecalculateCommand;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class PriceListRecalculateCommandTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadPriceListRelations::class,
            LoadProductPrices::class,
            LoadPriceListFallbackSettings::class,
        ]);
    }

    /**
     * @dataProvider commandDataProvider
     * @param $expectedMessage
     * @param array $params
     * @param int $expectedCount
     * @param array $websites
     * @param array $accountGroups
     * @param array $accounts
     */
    public function testCommand(
        $expectedMessage,
        array $params,
        $expectedCount,
        array $websites = [],
        array $accountGroups = [],
        array $accounts = []
    ) {
        $this->clearCombinedPrices();
        $this->assertCombinedPriceCount(0);

        $this->getContainer()->get('orob2b_pricing.builder.combined_price_list_builder')->resetCache();
        $this->getContainer()->get('orob2b_pricing.builder.website_combined_price_list_builder')->resetCache();
        $this->getContainer()->get('orob2b_pricing.builder.account_group_combined_price_list_builder')->resetCache();
        $this->getContainer()->get('orob2b_pricing.builder.account_combined_price_list_builder')->resetCache();

        foreach ($websites as $websiteName) {
            $params[] = '--website='.$this->getReference($websiteName)->getId();
        }

        foreach ($accountGroups as $accountGroupName) {
            $params[] = '--account-group='.$this->getReference($accountGroupName)->getId();
        }

        foreach ($accounts as $accountName) {
            $params[] = '--account='.$this->getReference($accountName)->getId();
        }

        $result = $this->runCommand(PriceListRecalculateCommand::NAME, $params);
        $this->assertContains($expectedMessage, $result);
        $this->assertCombinedPriceCount($expectedCount);
    }

    /**
     * @return array
     */
    public function commandDataProvider()
    {
        return [
            'all' => [
                'expected_message' => 'Start processing',
                'params' => ['--all'],
                'expectedCount' => 40 // 2 + 38 = config + website1
            ],
            'empty run' => [
                'expected_message' => 'ATTENTION',
                'params' => [],
                'expectedCount' => 0
            ],
            'website 1' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 38,
                'website' => [LoadWebsiteData::WEBSITE1],
                'accountGroup' => [],
                'account' => []
            ],
            'account.level_1_1' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 6,
                'website' => [],
                'accountGroup' => [],
                'account' => ['account.level_1_1']
            ],
            'account.level_1.2' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 4,
                'website' => [],
                'accountGroup' => [],
                'account' => ['account.level_1.2']
            ],
            'account.level_1.3' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 14,
                'website' => [],
                'accountGroup' => [],
                'account' => ['account.level_1.3']
            ],
            'account_group' => [
                'expected_message' => 'Start processing',
                'params' => [],
                'expectedCount' => 24, // 6 + 4 + 14 = account.level_1_1 + account.level_1.2 + account.level_1.3
                'website' => [],
                'accountGroup' => ['account_group.group1'], // doesn't has own price list
                'account' => []
            ],
        ];
    }

    /**
     * @param int $expectedCount
     */
    protected function assertCombinedPriceCount($expectedCount)
    {
        $combinedPrices = $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:CombinedProductPrice')
            ->createQueryBuilder('a')->getQuery()->getResult();

        $this->assertCount($expectedCount, $combinedPrices);
    }

    protected function clearCombinedPrices()
    {
        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:CombinedProductPrice')
            ->getRepository('OroPricingBundle:CombinedProductPrice')
            ->createQueryBuilder('combinedProductPrice')
            ->delete('OroPricingBundle:CombinedProductPrice', 'combinedProductPrice')
            ->getQuery()
            ->execute();
    }
}
