<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Query\Factory;

use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\Factory\CompositeQueryFactory;

class CompositeQueryFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $backendQueryFactory;

    /** @var QueryFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $websiteQueryFactory;

    /**
     * @var CompositeQueryFactory
     */
    protected $factory;

    protected function setUp(): void
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
