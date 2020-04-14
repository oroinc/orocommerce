<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class TaxProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var TaxProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider1;

    /** @var TaxProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider2;

    /** @var TaxProviderRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider1 = $this->createMock(TaxProviderInterface::class);
        $this->provider1->expects($this->any())
            ->method('isApplicable')
            ->willReturn(true);
        $this->provider2 = $this->createMock(TaxProviderInterface::class);
        $this->provider2->expects($this->any())
            ->method('isApplicable')
            ->willReturn(false);

        $providerContainer = TestContainerBuilder::create()
            ->add('provider1', $this->provider1)
            ->add('provider2', $this->provider2)
            ->getContainer($this);

        $this->registry = new TaxProviderRegistry(
            ['provider1', 'provider2'],
            $providerContainer,
            $this->configManager
        );
    }

    public function testGetProviders()
    {
        $providers = $this->registry->getProviders();
        $this->assertCount(1, $providers);
        $this->assertArrayHasKey('provider1', $providers);
        $this->assertSame($this->provider1, $providers['provider1']);
    }

    public function testGetProviderWhenProvidersCollectionIsNotInitializedYet()
    {
        $this->assertSame($this->provider1, $this->registry->getProvider('provider1'));
    }

    public function testGetProviderWhenProvidersCollectionIsAlreadyInitialized()
    {
        // initialize providers collection
        $this->registry->getProviders();

        $this->assertSame($this->provider1, $this->registry->getProvider('provider1'));
    }

    public function testGetProviderForNotApplicableProvider()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Tax provider with name "provider2" does not exist');

        $this->registry->getProvider('provider2');
    }

    public function testGetProviderForNotExistingProvider()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Tax provider with name "not_existing" does not exist');

        $this->registry->getProvider('not_existing');
    }

    public function testGetEnabledProvider()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_tax.tax_provider')
            ->willReturn('provider1');

        $this->assertSame($this->provider1, $this->registry->getEnabledProvider());
    }
}
