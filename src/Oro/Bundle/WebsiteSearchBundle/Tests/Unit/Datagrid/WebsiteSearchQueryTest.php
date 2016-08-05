<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Datagrid;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Datagrid\WebsiteSearchQuery;

class WebsiteSearchQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchQuery
     */
    protected $testable;

    /**
     * @var EngineV2Interface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $engine;

    /**
     * @var Query|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $query;

    public function setUp()
    {
        $this->engine = $this->getMockBuilder(EngineV2Interface::class)
            ->getMock();

        $this->query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testable = new WebsiteSearchQuery($this->engine, $this->query);
    }

    public function testExecuteShouldCallEngine()
    {
        $result = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result->expects($this->once())
            ->method('getElements');

        $this->engine->expects($this->once())
            ->method('search')
            ->with($this->query)
            ->willReturn($result);

        $this->testable->execute();
    }
}
