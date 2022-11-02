<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;

class ScopeWebCatalogProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebCatalogProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogProvider;

    /** @var ScopeWebCatalogProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->webCatalogProvider = $this->createMock(WebCatalogProvider::class);

        $this->provider = new ScopeWebCatalogProvider($this->webCatalogProvider);
    }

    public function testGetCriteriaField()
    {
        $this->assertEquals(ScopeWebCatalogProvider::WEB_CATALOG, $this->provider->getCriteriaField());
    }

    public function testGetCriteriaValue()
    {
        $webCatalog = new WebCatalog();

        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->willReturn($webCatalog);

        $this->assertSame($webCatalog, $this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueType()
    {
        $this->assertEquals(WebCatalog::class, $this->provider->getCriteriaValueType());
    }
}
