<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Layout\Extension\WebCatalogContextConfigurator;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\LayoutContext;

class WebCatalogContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var WebCatalogContextConfigurator */
    private $contextConfigurator;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

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
    public function testConfigureContext(?string $webCatalogId)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog')
            ->willReturn($webCatalogId);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals($webCatalogId, $context[WebCatalogContextConfigurator::CONTEXT_VARIABLE]);
    }

    public function allowedTypesDataProvider(): array
    {
        return [
            'null' => [null],
            'string' => ['1'],
        ];
    }

    /**
     * @dataProvider notAllowedTypesDataProvider
     */
    public function testConfigureContextTypeNotAllowed(mixed $webCatalogId)
    {
        $this->expectException(LogicException::class);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog')
            ->willReturn($webCatalogId);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals($webCatalogId, $context[WebCatalogContextConfigurator::CONTEXT_VARIABLE]);
    }

    public function notAllowedTypesDataProvider(): array
    {
        return [
            'array' => [[1]],
            'boolean' => [false],
            'object' => [new \stdClass()],
        ];
    }
}
