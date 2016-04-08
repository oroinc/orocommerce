<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Command\CombinedPriceListRecalculateCommand;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

class CombinedPriceListRecalculateCommandTest extends WebTestCase
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->configManager = $this->getContainer()->get('oro_config.manager');
    }

    /**
     * @dataProvider commandDataProvider
     * @param $modeValue
     * @param $expectedMessage
     */
    public function testCommand($modeValue, $expectedMessage)
    {
        /** @var  $manager */
        $configKey = Configuration::getConfigKeyByName(Configuration::PRICE_LISTS_UPDATE_MODE);
        $this->configManager->set($configKey, $modeValue);
        $this->configManager->flush();
        $result = $this->runCommand(CombinedPriceListRecalculateCommand::NAME);
        $this->assertContains($expectedMessage, $result);
    }

    /**
     * @return array
     */
    public function commandDataProvider()
    {
        return [
            'real_time' => [
                'mode_value' => CombinedPriceListQueueConsumer::MODE_REAL_TIME,
                'expected_message' => 'Recalculation is not required, another mode is active'
            ],
            'none' => [
                'mode_value' => null,
                'expected_message' => 'Recalculation is not required, another mode is active'
            ],
            'scheduled' => [
                'mode_value' => CombinedPriceListQueueConsumer::MODE_SCHEDULED,
                'expected_message' => 'The cache is updated successfully'
            ],
        ];
    }
}
