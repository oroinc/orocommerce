<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\Provider\ContactInfoAvailableUserOptionsProvider;

class ContactInfoAvailableUserOptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContactInfoAvailableUserOptionsProvider
     */
    private $provider;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new ContactInfoAvailableUserOptionsProvider($this->configManager);
    }

    public function testGetOptions()
    {
        static::assertEquals(
            [
                'dont_display',
                'user_profile_data',
                'enter_manually',
            ],
            $this->provider->getOptions()
        );
    }

    public function testGetSelectedOptions()
    {
        static::assertEquals(
            $this->provider->getSelectedOptions(),
            [
                'dont_display',
                'user_profile_data',
                'enter_manually',
            ]
        );
        $this->configManager->method('get')->willReturn(['pre_configured', 'user_profile_data']);
        static::assertEquals($this->provider->getSelectedOptions(), ['pre_configured', 'user_profile_data']);
    }
}
