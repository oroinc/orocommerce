<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataAuditBundle\Metadata\ClassMetadata;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexRequestListener;

class ReindexRequestListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASSNAME  = 'testClass';
    const TEST_WEBSITE_ID = 1234;

    /**
     * @var ReindexRequestListener
     */
    protected $listener;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManagerMock;

    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $regularIndexerMock;

    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $asyncIndexerMock;

    public function setUp()
    {
        $this->entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regularIndexerMock = $this->getMockBuilder(IndexerInterface::class)->getMock();
        $this->asyncIndexerMock = $this->getMockBuilder(IndexerInterface::class)->getMock();

        $this->listener = new ReindexRequestListener(
            $this->entityManagerMock,
            $this->regularIndexerMock,
            $this->asyncIndexerMock
        );
    }

    public function testProcess()
    {
        $event = new ReindexationTriggerEvent(
            self::TEST_CLASSNAME,
            self::TEST_WEBSITE_ID,
            null,
            false
        );

        $this->regularIndexerMock
            ->expects($this->once())
            ->method('reindex')
            ->with(self::TEST_CLASSNAME);

        $this->regularIndexerMock
            ->expects($this->never())
            ->method('save');
        $this->regularIndexerMock
            ->expects($this->never())
            ->method('delete');

        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('reindex');
        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('save');
        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('delete');


        $this->listener->process($event);
    }

    public function testProcessAsync()
    {
        $event = new ReindexationTriggerEvent(
            self::TEST_CLASSNAME,
            self::TEST_WEBSITE_ID,
            null,
            true
        );

        $this->asyncIndexerMock
            ->expects($this->once())
            ->method('reindex')
            ->with(self::TEST_CLASSNAME);

        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('save');
        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('delete');

        $this->regularIndexerMock
            ->expects($this->never())
            ->method('reindex');
        $this->regularIndexerMock
            ->expects($this->never())
            ->method('save');
        $this->regularIndexerMock
            ->expects($this->never())
            ->method('delete');

        $this->listener->process($event);
    }

    public function testProcessAtomic()
    {
        $event = new ReindexationTriggerEvent(
            self::TEST_CLASSNAME,
            self::TEST_WEBSITE_ID,
            [1, 2, 3, 4],
            false
        );

        $queryResult = [1, 3];
        $deleteIds = [2, 4];

        $this->regularIndexerMock
            ->expects($this->never())
            ->method('reindex');

        // Expr
        $exprMock = $this->getMockBuilder(Expr::class)
            ->disableOriginalConstructor()
            ->setMethods(['in'])
            ->getMock();
        $exprMock
            ->expects($this->once())
            ->method('in')
            ->with($this->matchesRegularExpression('/\.id$/'), $event->getIds());

        // Query
        $queryMock = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryMock
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResult);

        // QueryBuilder
        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilderMock
            ->expects($this->once())
            ->method('expr')
            ->willReturn($exprMock);
        $queryBuilderMock
            ->expects($this->once())
            ->method('andWhere')
            ->willReturn($queryBuilderMock);
        $queryBuilderMock
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($queryMock);

        // Repository
        $repositoryMock = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repositoryMock
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilderMock);
        $this->entityManagerMock
            ->expects($this->once())
            ->method('getRepository')
            ->with(self::TEST_CLASSNAME)
            ->willReturn($repositoryMock);

        // Metadata
        $metadataMock = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSingleIdentifierFieldName', 'getIdentifierValues'])
            ->getMock();
        $metadataMock
            ->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $metadataMock
            ->expects($this->at(1))
            ->method('getIdentifierValues')
            ->with($queryResult[0])
            ->willReturn([$queryResult[0]]);
        $metadataMock
            ->expects($this->at(2))
            ->method('getIdentifierValues')
            ->with($queryResult[1])
            ->willReturn([$queryResult[1]]);
        $this->entityManagerMock
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with(self::TEST_CLASSNAME)
            ->willReturn($metadataMock);

        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('reindex');
        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('save');
        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('delete');

        $this->entityManagerMock
            ->expects($this->any())
            ->method('getReference')
            ->with($event->getClassName())
            ->will($this->returnCallback(function($class, $id) {
                return $id;
            }));

        $this->regularIndexerMock
            ->expects($this->once())
            ->method('save')
            ->with($queryResult);

        $this->regularIndexerMock
            ->expects($this->once())
            ->method('delete')
            ->with($deleteIds);

        $this->listener->process($event);
    }

    /**
     * @expectedException \LogicException
     */
    public function testNoClassAndIdArray()
    {
        $event = new ReindexationTriggerEvent(
            null,
            self::TEST_WEBSITE_ID,
            [1, 2, 3, 4],
            false
        );

        $this->listener->process($event);
    }
}
