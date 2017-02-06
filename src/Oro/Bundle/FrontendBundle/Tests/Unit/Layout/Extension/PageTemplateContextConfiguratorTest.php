<?php


namespace Oro\Bundle\FrontendBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\DependencyInjection\OroFrontendExtension;
use Oro\Bundle\FrontendBundle\Layout\Extension\PageTemplateContextConfigurator;
use Oro\Component\Layout\LayoutContext;

class PageTemplateContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManagerMock;

    /** @var PageTemplateContextConfigurator */
    private $pageTemplateContextConfigurator;

    protected function setUp()
    {
        $this->configManagerMock = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTemplateContextConfigurator = new PageTemplateContextConfigurator($this->configManagerMock);
    }

    public function testConfigureContextPageTemplateAlreadySet()
    {
        $context = new LayoutContext();
        $context->set('page_template', 'some_page_template');
        $this->pageTemplateContextConfigurator->configureContext($context);
        $context->resolve();
        $this->assertSame('some_page_template', $context->get('page_template'));
    }

    public function testConfigureContextPageTemplateResolvedFromConfig()
    {
        $this->configManagerMock
            ->expects($this->once())
            ->method('get')
            ->with(OroFrontendExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . 'page_templates')
            ->willReturn(['some_route' => 'some_page_template']);

        $context = new LayoutContext();
        $context->getResolver()->setDefault('route_name', 'some_route');
        $this->pageTemplateContextConfigurator->configureContext($context);
        $context->resolve();
        $this->assertSame('some_page_template', $context->get('page_template'));
    }

    public function testConfigureContextPageTemplateNotAssigned()
    {
        $this->configManagerMock
            ->expects($this->once())
            ->method('get')
            ->with(OroFrontendExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . 'page_templates')
            ->willReturn(['some_route' => 'some_page_template']);

        $context = new LayoutContext();
        $context->getResolver()->setDefault('route_name', 'some_other_route');
        $this->pageTemplateContextConfigurator->configureContext($context);
        $context->resolve();
        $this->assertSame(null, $context->get('page_template'));
    }
}
