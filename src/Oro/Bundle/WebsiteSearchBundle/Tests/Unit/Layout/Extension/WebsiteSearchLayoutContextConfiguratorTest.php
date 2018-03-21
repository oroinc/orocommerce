<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\WebsiteSearchBundle\Layout\Extension\WebsiteSearchLayoutContextConfigurator;
use Oro\Bundle\WebsiteSearchBundle\QueryString\QueryStringProvider;
use Oro\Component\Layout\LayoutContext;

class WebsiteSearchLayoutContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebsiteSearchLayoutContextConfigurator */
    protected $contextConfigurator;

    /**
     * @var QueryStringProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryStringProvider;

    protected function setUp()
    {
        $this->queryStringProvider = $this->createMock(QueryStringProvider::class);

        $this->contextConfigurator = new WebsiteSearchLayoutContextConfigurator($this->queryStringProvider);
    }

    public function testConfigureContextWithDefaultAction()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertNull($context[WebsiteSearchLayoutContextConfigurator::SEARCH_QUERY_STRING]);
    }

    public function testConfigureContext()
    {
        $this->queryStringProvider
            ->expects($this->exactly(2))
            ->method('getSearchQueryString')
            ->willReturn('some keywords');

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertNotNull($context[WebsiteSearchLayoutContextConfigurator::SEARCH_QUERY_STRING]);
    }

    public function testConfigureContextNegative()
    {
        $this->queryStringProvider
            ->expects($this->once())
            ->method('getSearchQueryString')
            ->willReturn('');

        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertNull($context[WebsiteSearchLayoutContextConfigurator::SEARCH_QUERY_STRING]);
    }
}
