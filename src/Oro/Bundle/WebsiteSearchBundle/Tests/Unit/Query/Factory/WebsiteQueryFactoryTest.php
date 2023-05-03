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
        $this->queryFactory = $this->createMock(QueryFactoryInterface::class);
        $this->engine = $this->createMock(EngineInterface::class);
    }

    /**
     * @dataProvider configDataProvider
     */
    public function testCreate(array $config, string $expectedInstance)
    {
        $factory = new WebsiteQueryFactory($this->engine);

        $result = $factory->create($config);
        $this->assertInstanceOf($expectedInstance, $result);
    }

    public function configDataProvider(): \Generator
    {
        yield [
            [
                'search_index' => 'website',
                'query' => [
                    'select' => [
                        'text.sku',
                    ],
                    'from' => [
                        'product',
                    ],
                ],
            ],
            WebsiteSearchQuery::class
        ];

        yield [
            [
                'search_index' => 'website',
                'query' => [
                    'select' => [
                        'text.sku',
                    ],
                    'from' => [
                        'product',
                    ],
                ],
                'hints' => ['HINT_SEARCH_TYPE']
            ],
            WebsiteSearchQuery::class
        ];

        yield [
            [
                'search_index' => 'website',
                'query' => [
                    'select' => [
                        'text.sku',
                    ],
                    'from' => [
                        'product',
                    ],
                ],
                'hints' => ['name' => 'HINT_SEARCH_TYPE', 'value' => 'test']
            ],
            WebsiteSearchQuery::class
        ];

        yield [
            [
                'search_index' => null,
            ],
            SearchQueryInterface::class
        ];
    }
}
