<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ScopeWebCatalogProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ScopeWebCatalogProvider
     */
    protected $provider;

    /**
     * @var WebCatalogProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $webCatalogProvider;

    protected function setUp()
    {
        $this->webCatalogProvider = $this->getMockBuilder(WebCatalogProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ScopeWebCatalogProvider($this->webCatalogProvider);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $webCatalogId = 42;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $webCatalogId]);

        $this->webCatalogProvider->expects($this->once())
            ->method('getWebCatalog')
            ->willReturn($webCatalog);

        $this->assertEquals(
            ['webCatalog' => $webCatalog],
            $this->provider->getCriteriaForCurrentScope()
        );
    }

    public function testGetCriteriaField()
    {
        $this->assertEquals('webCatalog', $this->provider->getCriteriaField());
    }

    public function testGetCriteriaValueType()
    {
        $this->assertEquals(WebCatalog::class, $this->provider->getCriteriaValueType());
    }
}
