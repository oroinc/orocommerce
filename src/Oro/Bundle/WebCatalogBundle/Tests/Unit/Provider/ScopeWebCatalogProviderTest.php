<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;

class ScopeWebCatalogProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ScopeWebCatalogProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ScopeWebCatalogProvider($this->configManager);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog')
            ->willReturn(42);

        $this->assertEquals(
            ['webCatalog' => 42],
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
