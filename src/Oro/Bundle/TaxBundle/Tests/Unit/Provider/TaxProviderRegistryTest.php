<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class TaxProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var TaxProviderRegistry
     */
    protected $registry;

    public function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->registry = new TaxProviderRegistry($this->configManager);
    }

    public function tearDown()
    {
        unset($this->registry);
    }

    public function testAddProvider()
    {
        $providerName = 'TestProvider';
        $provider = $this->getProviderMock($providerName);
        $provider->expects($this->once())
            ->method('isApplicable')
            ->willReturn(true);

        $this->registry->addProvider($provider);
        $this->assertCount(1, $this->registry->getProviders());
        $this->assertSame($provider, current($this->registry->getProviders()));
    }

    public function testAddProviderNotApplicable()
    {
        $providerName = 'TestProvider';
        $provider = $this->getProviderMock($providerName);
        $provider->expects($this->once())
            ->method('isApplicable')
            ->willReturn(false);

        $this->registry->addProvider($provider);
        $this->assertCount(0, $this->registry->getProviders());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Tax provider with name "TestProvider" already registered
     */
    public function testAddTwoProvidersWithSameName()
    {
        $providerName = 'TestProvider';

        $provider = $this->getProviderMock($providerName);
        $provider->expects($this->once())
            ->method('isApplicable')
            ->willReturn(true);

        $this->registry->addProvider($provider);
        $this->registry->addProvider($this->getProviderMock($providerName));
    }

    public function testGetProvider()
    {
        $providerName = 'TestProvider';
        $expectedProvider = $this->getProviderMock($providerName);
        $expectedProvider->expects($this->once())
            ->method('isApplicable')
            ->willReturn(true);
        $this->registry->addProvider($expectedProvider);
        $actualProvider = $this->registry->getProvider($providerName);

        $this->assertSame($expectedProvider, $actualProvider);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Tax provider with name "someProviderName" does not exist
     */
    public function testGetProviderWithUnknownName()
    {
        $this->registry->getProvider('someProviderName');
    }

    public function testGetEnabledProvider()
    {
        $providerName = 'TestProvider';

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_tax.tax_provider')
            ->willReturn($providerName);

        $expectedProvider = $this->getProviderMock($providerName);
        $expectedProvider->expects($this->once())
            ->method('isApplicable')
            ->willReturn(true);
        $this->registry->addProvider($expectedProvider);
        $actualProvider = $this->registry->getEnabledProvider();

        $this->assertSame($expectedProvider, $actualProvider);
    }

    /**
     * @param string $name
     * @return TaxProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProviderMock($name)
    {
        $mock = $this->createMock('Oro\Bundle\TaxBundle\Provider\TaxProviderInterface');
        $mock->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $mock;
    }
}
