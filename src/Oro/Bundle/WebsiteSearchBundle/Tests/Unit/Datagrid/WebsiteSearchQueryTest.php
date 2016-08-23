<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Datagrid;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    public function setUp()
    {
        $this->engine = $this->getMockBuilder(EngineV2Interface::class)
            ->getMock();

        $this->query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getMock(EventDispatcherInterface::class);

        $this->testable = new WebsiteSearchQuery(
            $this->engine,
            $this->dispatcher,
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

        $this->testable->addSelect($name, $type);
    }

    public function testFrom()
    {
        $alias = 'alias';

        $this->query->expects($this->once())
            ->method('from')
            ->with($alias);

        $this->testable->from($alias);
    }

    public function testExecuteShouldCallEngine()
    {
        $result = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectedData = ['foo'];

        $this->query->expects($this->at(0))
            ->method('getSelect')
            ->willReturn($selectedData);

        $this->dispatcher->expects($this->once())
            ->method('dispatch');

        $this->query->expects($this->at(1))
            ->method('select')
            ->with($selectedData);

        $result->expects($this->once())
            ->method('getElements');

        $this->engine->expects($this->once())
            ->method('search')
            ->with($this->query)
            ->willReturn($result);

        $this->testable->execute();
    }
}
