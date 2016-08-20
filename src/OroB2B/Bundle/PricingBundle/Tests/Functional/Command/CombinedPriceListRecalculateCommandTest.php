<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use Oro\Bundle\PricingBundle\Command\CombinedPriceListRecalculateCommand;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class CombinedPriceListRecalculateCommandTest extends WebTestCase
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->loadFixtures([
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings',
        ]);
    }

    /**
     * @dataProvider commandDataProvider
     * @param $modeValue
     * @param $expectedMessage
     * @param array $params
     * @param int $expectedCount
     * @param array $websites
     * @param array $accountGroups
     * @param array $accounts
     */
    public function testCommand(
        $modeValue,
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

        $this->clearTriggers();

        /** @var  $manager */
        $configKey = Configuration::getConfigKeyByName(Configuration::PRICE_LISTS_UPDATE_MODE);
        $this->configManager->set($configKey, $modeValue);
        $this->configManager->flush();

        foreach ($websites as $websiteName) {
            $params[] = '--website='.$this->getReference($websiteName)->getId();
        }

        foreach ($accountGroups as $accountGroupName) {
            $params[] = '--account-group='.$this->getReference($accountGroupName)->getId();
        }

        foreach ($accounts as $accountName) {
            $params[] = '--account='.$this->getReference($accountName)->getId();
        }

        $result = $this->runCommand(CombinedPriceListRecalculateCommand::NAME, $params);
        $this->assertContains($expectedMessage, $result);
        $this->assertCombinedPriceCount($expectedCount);
    }

    /**
     * @return array
     */
    public function commandDataProvider()
    {
        return [
            'scheduled with force' => [
                'mode_value' => CombinedPriceListQueueConsumer::MODE_SCHEDULED,
                'expected_message' => 'The cache is updated successfully',
                'params' => ['--force'],
                'expectedCount' => 40
            ],
            'real_time' => [
                'mode_value' => CombinedPriceListQueueConsumer::MODE_REAL_TIME,
                'expected_message' => 'Recalculation is not required, another mode is active',
                'params' => [],
                'expectedCount' => 0
            ],
            'scheduled without force' => [
                'mode_value' => CombinedPriceListQueueConsumer::MODE_SCHEDULED,
                'expected_message' => 'The cache is updated successfully',
                'params' => [],
                'expectedCount' => 0
            ],
            'scheduled website US with force' => [
                'mode_value' => CombinedPriceListQueueConsumer::MODE_SCHEDULED,
                'expected_message' => 'The cache is updated successfully',
                'params' => ['--force'],
                'expectedCount' => 38,
                'website' => [LoadWebsiteData::WEBSITE1],
                'accountGroup' => [],
                'account' => []
            ],
            'scheduled account.level_1_1 without force' => [
                'mode_value' => CombinedPriceListQueueConsumer::MODE_SCHEDULED,
                'expected_message' => 'ATTENTION: To force execution run command with --force option:
    oro:cron:price-lists:recalculate --force',
                'params' => [],
                'expectedCount' => 0,
                'website' => ['US'],
                'accountGroup' => [],
                'account' => ['account.level_1_1']
            ],
            'scheduled account_group.group1 with force' => [
                'mode_value' => CombinedPriceListQueueConsumer::MODE_SCHEDULED,
                'expected_message' => 'The cache is updated successfully',
                'params' => ['--force'],
                'expectedCount' => 30,
                'website' => [],
                'accountGroup' => ['account_group.group1'],
                'account' => []
            ],
        ];
    }

    protected function clearTriggers()
    {
        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:ProductPriceChangeTrigger')
            ->getRepository('OroPricingBundle:ProductPriceChangeTrigger')
            ->createQueryBuilder('productPriceChangeTrigger')
            ->delete('OroPricingBundle:ProductPriceChangeTrigger', 'productPriceChangeTrigger')
            ->getQuery()
            ->execute();

        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:PriceListChangeTrigger')
            ->getRepository('OroPricingBundle:PriceListChangeTrigger')
            ->createQueryBuilder('priceListChangeTrigger')
            ->delete('OroPricingBundle:PriceListChangeTrigger', 'priceListChangeTrigger')
            ->getQuery()
            ->execute();

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
