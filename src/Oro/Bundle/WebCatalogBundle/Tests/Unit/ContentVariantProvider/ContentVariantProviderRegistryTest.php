<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentVariantProvider;

use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Bundle\WebCatalogBundle\ContentVariantProvider\ContentVariantProviderRegistry;

class ContentVariantProviderRegistryTest extends \PHPUnit_Framework_TestCase
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
        /** @var ContentVariantProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(ContentVariantProviderInterface::class);
        $this->registry->addProvider($provider);

        $this->assertEquals([$provider], $this->registry->getProviders());
    }

    public function testGetProviders()
    {
        /** @var ContentVariantProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider1 */
        $provider1 = $this->getMock(ContentVariantProviderInterface::class);

        /** @var ContentVariantProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider2 */
        $provider2 = $this->getMock(ContentVariantProviderInterface::class);

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
