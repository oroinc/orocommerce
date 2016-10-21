<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\PageProvider;

use Oro\Component\WebCatalog\PageProviderInterface;
use Oro\Bundle\WebCatalogBundle\PageProvider\WebCatalogPageProviderRegistry;

class WebCatalogPageProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebCatalogPageProviderRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new WebCatalogPageProviderRegistry();
    }

    public function testAddPageProvider()
    {
        $providerName = 'provider_name';

        /** @var PageProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(PageProviderInterface::class);
        $provider->expects($this->any())
            ->method('getName')
            ->willReturn($providerName);

        $this->registry->addProvider($provider);

        $this->assertEquals([$providerName => $provider], $this->registry->getProviders());
    }

    public function testGetPageProvider()
    {
        /** @var PageProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(PageProviderInterface::class);

        $provider->expects($this->any())
            ->method('getName')
            ->willReturn('provider_name');

        $this->registry->addProvider($provider);

        $actualProvider = $this->registry->getProvider($provider->getName());
        $this->assertSame($provider, $actualProvider);
    }

    public function testGetPlaceholderException()
    {
        $unknownProviderName = 'unknown';
        $this->setExpectedException(
            'Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException',
            sprintf('Page provider "%s" does not exist.', $unknownProviderName)
        );

        $this->registry->getProvider($unknownProviderName);
    }

    public function testGetProviders()
    {
        $provider1Name = 'provider_1';
        $provider2Name = 'provider_2';
        
        /** @var PageProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider1 */
        $provider1 = $this->getMock(PageProviderInterface::class);
        $provider1->expects($this->any())
            ->method('getName')
            ->willReturn($provider1Name);

        /** @var PageProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider2 */
        $provider2 = $this->getMock(PageProviderInterface::class);
        $provider2->expects($this->any())
            ->method('getName')
            ->willReturn($provider2Name);

        $this->registry->addProvider($provider1);
        $this->registry->addProvider($provider2);
        $this->assertEquals(
            [
                $provider1Name => $provider1,
                $provider2Name => $provider2
            ],
            $this->registry->getProviders()
        );
    }
}
