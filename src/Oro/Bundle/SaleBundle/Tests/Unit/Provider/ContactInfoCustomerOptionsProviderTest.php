<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\Provider\ContactInfoSourceOptionsProvider;

class ContactInfoCustomerOptionsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContactInfoSourceOptionsProvider
     */
    private $provider;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new ContactInfoSourceOptionsProvider($this->configManager);
    }

    public function testGetOptions()
    {
        static::assertEquals(
            [
                'dont_display',
                'customer_user_owner',
                'customer_owner',
                'pre_configured',
            ],
            $this->provider->getOptions()
        );
    }

    public function testIsSelectedOptionPreConfigured()
    {
        $this->configManager->method('get')->willReturn('pre_configured');
        static::assertTrue($this->provider->isSelectedOptionPreConfigured());
    }

    public function testGetSelectedOption()
    {
        $this->configManager->method('get')->willReturn('pre_configured');
        static::assertEquals($this->provider->getSelectedOption(), 'pre_configured');
    }
}
