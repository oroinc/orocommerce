<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProvider;

class OrderConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var OrderConfigurationProvider */
    protected $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new OrderConfigurationProvider($this->configManager);
    }

    public function testGetNewOrderInternalStatus()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::CONFIG_KEY_NEW_ORDER_INTERNAL_STATUS), false, true, null)
            ->willReturn(['value' => 'data']);

        $this->assertEquals('data', $this->provider->getNewOrderInternalStatus(new Order()));
    }

    public function testGetNewOrderInternalStatusEmptyConfig()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::CONFIG_KEY_NEW_ORDER_INTERNAL_STATUS), false, true, null)
            ->willReturn([]);

        $this->assertNull($this->provider->getNewOrderInternalStatus(new Order()));
    }

    public function testIsAutomaticCancellationEnabled()
    {
        $identifier = new \stdClass();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::CONFIG_KEY_ENABLE_CANCELLATION), false, true, $identifier)
            ->willReturn(['value' => 'data']);

        $this->assertEquals('data', $this->provider->isAutomaticCancellationEnabled($identifier));
    }

    public function testIsAutomaticCancellationEnabledEmptyConfig()
    {
        $identifier = new \stdClass();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKey(Configuration::CONFIG_KEY_ENABLE_CANCELLATION), false, true, $identifier)
            ->willReturn([]);

        $this->assertNull($this->provider->isAutomaticCancellationEnabled($identifier));
    }

    public function testGetTargetInternalStatus()
    {
        $identifier = new \stdClass();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(
                Configuration::getConfigKey(Configuration::CONFIG_KEY_TARGET_INTERNAL_STATUS),
                false,
                true,
                $identifier
            )
            ->willReturn(['value' => 'data']);

        $this->assertEquals('data', $this->provider->getTargetInternalStatus($identifier));
    }

    public function testGetTargetInternalStatusEmptyConfig()
    {
        $identifier = new \stdClass();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(
                Configuration::getConfigKey(Configuration::CONFIG_KEY_TARGET_INTERNAL_STATUS),
                false,
                true,
                $identifier
            )
            ->willReturn([]);

        $this->assertNull($this->provider->getTargetInternalStatus($identifier));
    }

    public function testGetApplicableInternalStatuses()
    {
        $identifier = new \stdClass();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(
                Configuration::getConfigKey(Configuration::CONFIG_KEY_APPLICABLE_INTERNAL_STATUSES),
                false,
                true,
                $identifier
            )
            ->willReturn(['value' => 'data']);

        $this->assertEquals('data', $this->provider->getApplicableInternalStatuses($identifier));
    }

    public function testGetApplicableInternalStatusesEmptyConfig()
    {
        $identifier = new \stdClass();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(
                Configuration::getConfigKey(Configuration::CONFIG_KEY_APPLICABLE_INTERNAL_STATUSES),
                false,
                true,
                $identifier
            )
            ->willReturn([]);

        $this->assertNull($this->provider->getApplicableInternalStatuses($identifier));
    }
}
