<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistry;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;

class UrlItemsProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UrlItemsProviderRegistry
     */
    private $registry;

    protected function setUp(): void
    {
        $providers = [
            'first_provider' => $this->getProviderMock(),
            'second_provider' => $this->getProviderMock()
        ];
        $this->registry = new UrlItemsProviderRegistry($providers);
    }

    public function testGetProviders()
    {
        $this->assertEquals(
            [
                'first_provider' => $this->getProviderMock(),
                'second_provider' => $this->getProviderMock()
            ],
            $this->registry->getProvidersIndexedByNames()
        );
    }

    public function testGetProviderByName()
    {
        $this->assertEquals(
            $this->getProviderMock(),
            $this->registry->getProviderByName('first_provider')
        );
        $this->assertNull($this->registry->getProviderByName('some_other_name'));
    }

    /**
     * @return UrlItemsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getProviderMock()
    {
        return $this->createMock(UrlItemsProviderInterface::class);
    }
}
