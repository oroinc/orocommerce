<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Command\CombinedPriceListRecalculateCommand;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

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
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings',
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
                'expectedCount' => 24,
                'website' => [],
                'accountGroup' => ['account_group.group1'],
                'account' => []
            ],
        ];
    }

    protected function clearTriggers()
    {
        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:ProductPriceChangeTrigger')
            ->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger')
            ->createQueryBuilder('productPriceChangeTrigger')
            ->delete('OroB2BPricingBundle:ProductPriceChangeTrigger', 'productPriceChangeTrigger')
            ->getQuery()
            ->execute();

        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceListChangeTrigger')
            ->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')
            ->createQueryBuilder('priceListChangeTrigger')
            ->delete('OroB2BPricingBundle:PriceListChangeTrigger', 'priceListChangeTrigger')
            ->getQuery()
            ->execute();

    }

    /**
     * @param int $expectedCount
     */
    protected function assertCombinedPriceCount($expectedCount)
    {
        $combinedPrices = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPricingBundle:CombinedProductPrice')
            ->createQueryBuilder('a')->getQuery()->getResult();

        $this->assertCount($expectedCount, $combinedPrices);
    }

    protected function clearCombinedPrices()
    {
        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:CombinedProductPrice')
            ->getRepository('OroB2BPricingBundle:CombinedProductPrice')
            ->createQueryBuilder('combinedProductPrice')
            ->delete('OroB2BPricingBundle:CombinedProductPrice', 'combinedProductPrice')
            ->getQuery()
            ->execute();
    }
}
