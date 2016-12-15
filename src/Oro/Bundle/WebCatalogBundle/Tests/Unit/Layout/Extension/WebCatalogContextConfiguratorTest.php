<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Layout\Extension\WebCatalogContextConfigurator;

class WebCatalogContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebCatalogContextConfigurator */
    protected $contextConfigurator;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextConfigurator = new WebCatalogContextConfigurator($this->configManager);
    }

    public function testConfigureContextWithDefaultAction()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertNull($context[WebCatalogContextConfigurator::CONTEXT_VARIABLE]);
    }

    public function testConfigureContext()
    {
        $webCatalogId = '1';

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog')
            ->willReturn($webCatalogId);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals($webCatalogId, $context[WebCatalogContextConfigurator::CONTEXT_VARIABLE]);
    }
}
