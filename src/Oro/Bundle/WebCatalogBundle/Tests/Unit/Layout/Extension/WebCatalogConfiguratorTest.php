<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\WebCatalogBundle\Layout\Extension\WebCatalogContextConfigurator;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;

class WebCatalogConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebCatalogContextConfigurator */
    protected $contextConfigurator;

    /**
     * @var ScopeWebCatalogProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeWebCatalogProvider;

    protected function setUp()
    {
        $this->scopeWebCatalogProvider = $this->getMockBuilder(ScopeWebCatalogProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextConfigurator = new WebCatalogContextConfigurator($this->scopeWebCatalogProvider);
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

        $this->scopeWebCatalogProvider
            ->expects($this->once())
            ->method('getCriteriaForCurrentScope')
            ->willReturn([ ScopeWebCatalogProvider::WEB_CATALOG => $webCatalogId]);

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals($webCatalogId, $context[WebCatalogContextConfigurator::CONTEXT_VARIABLE]);
    }
}
