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
    protected $queryFactory;

    /** @var EngineV2Interface|\PHPUnit_Framework_MockObject_MockBuilder */
    protected $engine;

    /** @var SearchQueryInterface|\PHPUnit_Framework_MockObject_MockBuilder */
    protected $query;

    public function setUp()
    {
        $this->query        = $this->getMockBuilder(SearchQueryInterface::class)->getMock();
        $this->queryFactory = $this->getMockBuilder(QueryFactoryInterface::class)->setMethods(['create'])->getMock();
        $this->queryFactory->method('create')->willReturn($this->query);
        $this->engine = $this->getMock(EngineV2Interface::class);
    }

    public function testCreate()
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

        $configForBackendSearch = [
            'search_index' => null
        ];

        $this->queryFactory->expects($this->once())
            ->method('create')
            ->with($configForBackendSearch);

        $factory = new CompositeQueryFactory($this->queryFactory, $this->engine);

        $backend = $factory->create($configForBackendSearch);

        $this->assertInstanceOf(SearchQueryInterface::class, $backend);

        $frontend = $factory->create($configForWebsiteSearch);

        $this->assertInstanceOf(WebsiteSearchQuery::class, $frontend);
    }
}
