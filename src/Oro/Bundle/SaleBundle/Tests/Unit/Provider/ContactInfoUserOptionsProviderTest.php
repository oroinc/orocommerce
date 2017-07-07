<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\Provider\ContactInfoAvailableUserOptionsProvider;
use Oro\Bundle\SaleBundle\Provider\ContactInfoSourceOptionsProvider;
use Oro\Bundle\SaleBundle\Provider\ContactInfoUserOptionsProvider;

class ContactInfoUserOptionsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContactInfoSourceOptionsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerOptionProvider;

    /**
     * @var ContactInfoAvailableUserOptionsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $availableUserOptionsProvider;

    /**
     * @var ContactInfoUserOptionsProvider
     */
    private $provider;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->customerOptionProvider = $this->createMock(ContactInfoSourceOptionsProvider::class);
        $this->availableUserOptionsProvider = $this->createMock(ContactInfoAvailableUserOptionsProvider::class);
        $this->provider = new ContactInfoUserOptionsProvider(
            $this->configManager,
            $this->availableUserOptionsProvider,
            $this->customerOptionProvider
        );
    }

    public function testGetOptions()
    {
        $this->availableUserOptionsProvider->method('getSelectedOptions')->willReturn(['first option']);
        static::assertEquals(
            [
                'first option',
            ],
            $this->provider->getOptions()
        );
    }

    public function testGetOptionsWithSystem()
    {
        $this->availableUserOptionsProvider->method('getSelectedOptions')->willReturn(['first option']);
        $this->customerOptionProvider->method('isSelectedOptionPreConfigured')->willReturn(true);
        static::assertEquals(
            [
                'first option',
                'use_system'
            ],
            $this->provider->getOptions()
        );
    }

    public function testGetSelectedOptions()
    {
        $this->availableUserOptionsProvider
            ->method('getSelectedOptions')
            ->willReturn(
                [
                    'dont_display',
                    'user_profile_data',
                    'enter_manually',
                ]
            );
        static::assertEquals('dont_display', $this->provider->getSelectedOption());

        $this->customerOptionProvider->method('getSelectedOption')->willReturn('customer_owner');
        static::assertEquals('user_profile_data', $this->provider->getSelectedOption());

        $this->customerOptionProvider->method('getSelectedOption')->willReturn('customer_user_owner');
        static::assertEquals('user_profile_data', $this->provider->getSelectedOption());

        $this->configManager->method('get')->willReturn('customer_user_owner');
        static::assertEquals('user_profile_data', $this->provider->getSelectedOption());
    }
}
