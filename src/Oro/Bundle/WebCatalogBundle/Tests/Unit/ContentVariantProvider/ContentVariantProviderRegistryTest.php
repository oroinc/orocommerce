<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentVariantProvider;

use Oro\Bundle\WebCatalogBundle\ContentVariantProvider\ContentVariantProviderRegistry;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;

class ContentVariantProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContentVariantProviderRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new ContentVariantProviderRegistry();
    }

    public function testAddContentVariantProvider()
    {
        /** @var ContentVariantProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider */
        $provider = $this->createMock(ContentVariantProviderInterface::class);
        $this->registry->addProvider($provider);

        $this->assertEquals([$provider], $this->registry->getProviders());
    }

    public function testGetProviders()
    {
        /** @var ContentVariantProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider1 */
        $provider1 = $this->createMock(ContentVariantProviderInterface::class);

        /** @var ContentVariantProviderInterface|\PHPUnit\Framework\MockObject\MockObject $provider2 */
        $provider2 = $this->createMock(ContentVariantProviderInterface::class);

        $this->registry->addProvider($provider1);
        $this->registry->addProvider($provider2);
        $this->assertEquals(
            [
                $provider1,
                $provider2
            ],
            $this->registry->getProviders()
        );
    }
}
