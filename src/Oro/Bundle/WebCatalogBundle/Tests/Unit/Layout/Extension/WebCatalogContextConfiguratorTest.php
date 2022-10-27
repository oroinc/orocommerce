<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Layout\Extension\WebCatalogContextConfigurator;
use Oro\Component\Layout\LayoutContext;

class WebCatalogContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebCatalogContextConfigurator */
    protected $contextConfigurator;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    protected function setUp(): void
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

    /**
     * @dataProvider allowedTypesDataProvider
     */
    public function testConfigureContext($webCatalogId)
    {
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

    /**
     * @return array
     */
    public function allowedTypesDataProvider()
    {
        return [
            'null' => [null],
            'string' => ['1'],
        ];
    }

    /**
     * @dataProvider notAllowedTypesDataProvider
     */
    public function testConfigureContextTypeNotAllowed($webCatalogId)
    {
        $this->expectException(\Oro\Component\Layout\Exception\LogicException::class);
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

    /**
     * @return array
     */
    public function notAllowedTypesDataProvider()
    {
        return [
            'array' => [[1]],
            'boolean' => [false],
            'object' => [new \stdClass()],
        ];
    }
}
