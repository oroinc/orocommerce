<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\Factory\WebsiteQueryFactory;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class WebsiteQueryFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $queryFactory;

    /** @var EngineInterface|\PHPUnit\Framework\MockObject\MockBuilder */
    protected $engine;

    protected function setUp(): void
    {
        $this->queryFactory    = $this->createMock(QueryFactoryInterface::class);
        $this->engine          = $this->createMock(EngineInterface::class);
    }

    public function testCreate()
    {
        $configForWebsiteSearch = [
            'search_index' => 'website',
            'query' => [
                'select' => [
                    'text.sku'
                ],
                'from' => [
                    'product'
                ]
            ]
        ];

        $configForBackendSearch = [
            'search_index' => null
        ];

        $factory = new WebsiteQueryFactory($this->engine);

        $result = $factory->create($configForBackendSearch);

        $this->assertInstanceOf(SearchQueryInterface::class, $result);

        $result = $factory->create($configForWebsiteSearch);

        $this->assertInstanceOf(WebsiteSearchQuery::class, $result);
    }
}
