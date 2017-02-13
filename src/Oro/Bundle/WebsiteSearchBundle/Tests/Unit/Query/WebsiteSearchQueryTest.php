<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class WebsiteSearchQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebsiteSearchQuery */
    protected $websiteSearchQuery;

    /** @var EngineInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $engine;

    /** @var Query|\PHPUnit_Framework_MockObject_MockObject */
    protected $query;

    public function setUp()
    {
        $this->engine = $this->getMockBuilder(EngineInterface::class)
            ->getMock();

        $this->query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteSearchQuery = new WebsiteSearchQuery(
            $this->engine,
            $this->query
        );
    }

    public function testAddSelect()
    {
        $name = 'name';
        $type = 'text';

        $this->query->expects($this->once())
            ->method('addSelect')
            ->with($name, $type);

        $this->websiteSearchQuery->addSelect($name, $type);
    }

    public function testFrom()
    {
        $alias = 'alias';

        $this->query->expects($this->once())
            ->method('from')
            ->with($alias);

        $this->websiteSearchQuery->setFrom($alias);
    }

    public function testWebsiteQueryExecution()
    {
        $result = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->engine->expects($this->once())
            ->method('search')
            ->with($this->query)
            ->willReturn($result);

        $this->websiteSearchQuery->execute();
    }
}
