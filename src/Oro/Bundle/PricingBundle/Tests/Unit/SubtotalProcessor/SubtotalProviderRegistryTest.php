<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class SubtotalProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider1;

    /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider2;

    /** @var SubtotalProviderRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(SubtotalProviderInterface::class);
        $this->provider2 = $this->createMock(SubtotalProviderInterface::class);

        $providerContainer = TestContainerBuilder::create()
            ->add('provider1', $this->provider1)
            ->add('provider2', $this->provider2)
            ->getContainer($this);

        $this->registry = new SubtotalProviderRegistry(
            ['provider1', 'provider2'],
            $providerContainer
        );
    }

    public function testGetProviders()
    {
        $providers = $this->registry->getProviders();
        $this->assertCount(2, $providers);
        $this->assertSame($this->provider1, $providers['provider1']);
        $this->assertSame($this->provider2, $providers['provider2']);
    }

    public function testGetSupportedProviders()
    {
        $entity = new \stdClass();

        $this->provider1->expects($this->once())
            ->method('isSupported')
            ->with($this->identicalTo($entity))
            ->willReturn(false);
        $this->provider2->expects($this->once())
            ->method('isSupported')
            ->with($this->identicalTo($entity))
            ->willReturn(true);

        $providers = $this->registry->getSupportedProviders($entity);
        $this->assertCount(1, $providers);
        $this->assertSame($this->provider2, $providers['provider2']);
    }

    public function testGetProviderByNameWhenProvidersCollectionIsNotInitializedYet()
    {
        $this->assertSame($this->provider1, $this->registry->getProviderByName('provider1'));
    }

    public function testGetProviderByNameWhenProvidersCollectionIsAlreadyInitialized()
    {
        // initialize providers collection
        $this->registry->getProviders();

        $this->assertSame($this->provider1, $this->registry->getProviderByName('provider1'));
    }

    public function testGetProviderByNameForNotExistingProvider()
    {
        $this->assertNull($this->registry->getProviderByName('not_existing'));
    }

    public function testHasProvider()
    {
        $this->assertTrue($this->registry->hasProvider('provider1'));
        $this->assertTrue($this->registry->hasProvider('provider2'));
        $this->assertFalse($this->registry->hasProvider('not_existing'));
    }
}
