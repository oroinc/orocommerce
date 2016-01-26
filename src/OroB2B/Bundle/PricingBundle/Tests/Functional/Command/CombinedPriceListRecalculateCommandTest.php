<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\Command\CombinedPriceListRecalculateCommand;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

class CombinedPriceListRecalculateCommandTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * @dataProvider commandDataProvider
     * @param $modeValue
     * @param $expectedMessage
     */
    public function testCommand($modeValue, $expectedMessage)
    {
        /** @var  $manager */

        $this->getContainer()->get('oro_config.manager')->set(
            'orob2b_pricing.' . Configuration::PRICE_LISTS_UPDATE_MODE,
            $modeValue
        );
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
                'expected_message' => 'Recalculation is not required for real time mode'
            ],
            'none' => [
                'mode_value' => null,
                'expected_message' => 'Recalculation mode is not defined'
            ],
            'scheduled' => [
                'mode_value' => CombinedPriceListQueueConsumer::MODE_SCHEDULED,
                'expected_message' => 'The cache is updated successfully'
            ],
        ];
    }
}
