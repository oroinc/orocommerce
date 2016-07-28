<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\PayPalBundle\EventListener\Config\PayPalSelectedCountriesListener;
use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;

class PayPalSelectedCountriesListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayPalSelectedCountriesListener */
    protected $listener;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PayPalSelectedCountriesListener();
    }

    protected function tearDown()
    {
        unset($this->listener, $this->configManager);
    }

    public function testBeforeSave()
    {
        $settings = [
            Configuration::getFullConfigKey(Configuration::PAYFLOW_GATEWAY_ALLOWED_COUNTRIES_KEY) => [
                'value' => PaymentConfiguration::ALLOWED_COUNTRIES_ALL
            ],
            Configuration::getFullConfigKey(Configuration::PAYFLOW_GATEWAY_SELECTED_COUNTRIES_KEY) => [
                'use_parent_scope_value' => false,
                'value' => ['Country']
            ],
            Configuration::getFullConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_COUNTRIES_KEY) => [
                'value' => PaymentConfiguration::ALLOWED_COUNTRIES_SELECTED
            ],
            Configuration::getFullConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_SELECTED_COUNTRIES_KEY) => [
                'use_parent_scope_value' => false,
                'value' => ['Country']
            ],
        ];

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);
        $this->listener->beforeSave($event);

        $expected = [
            Configuration::getFullConfigKey(Configuration::PAYFLOW_GATEWAY_ALLOWED_COUNTRIES_KEY) => [
                'value' => PaymentConfiguration::ALLOWED_COUNTRIES_ALL
            ],
            Configuration::getFullConfigKey(Configuration::PAYFLOW_GATEWAY_SELECTED_COUNTRIES_KEY) => [
                'use_parent_scope_value' => true,
                'value' => ['Country']
            ],
            Configuration::getFullConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_COUNTRIES_KEY) => [
                'value' => PaymentConfiguration::ALLOWED_COUNTRIES_SELECTED
            ],
            Configuration::getFullConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_SELECTED_COUNTRIES_KEY) => [
                'use_parent_scope_value' => false,
                'value' => ['Country']
            ],
        ];

        $this->assertEquals($expected, $event->getSettings());
    }
}
