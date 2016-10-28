<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Bundle\WebsiteSearchBundle\Query\Factory\CompositeQueryFactory;

class CompositeQueryFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $backendQueryFactory;

    /** @var QueryFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $websiteQueryFactory;

    /**
     * @var CompositeQueryFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->backendQueryFactory = $this->getMockBuilder(QueryFactoryInterface::class)->getMock();
        $this->websiteQueryFactory = $this->getMockBuilder(QueryFactoryInterface::class)->getMock();

        $this->factory = new CompositeQueryFactory($this->backendQueryFactory, $this->websiteQueryFactory);
    }

    public function testCreateWebsite()
    {
        $configForWebsiteSearch = [
            'search_index' => 'website',
            'query'        => [
                'select' => [
                    'text.sku'
                ],
                'from'   => [
                    'product'
                ]
            ]
        ];

        $query = $this->getMockBuilder(SearchQueryInterface::class)->getMock();

        $this->backendQueryFactory->expects($this->never())
            ->method('create');
        $this->websiteQueryFactory->expects($this->once())
            ->method('create')
            ->with($configForWebsiteSearch)
            ->willReturn($query);

        $this->assertEquals($query, $this->factory->create($configForWebsiteSearch));
    }

    public function testCreateBackend()
    {
        $configForBackendSearch = [
            'search_index' => null
        ];

        $query = $this->getMockBuilder(SearchQueryInterface::class)->getMock();

        $this->backendQueryFactory->expects($this->once())
            ->method('create')
            ->with($configForBackendSearch)
            ->willReturn($query);
        $this->websiteQueryFactory->expects($this->never())
            ->method('create');

        $this->assertEquals($query, $this->factory->create($configForBackendSearch));
    }
}
