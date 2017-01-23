<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\RedirectBundle\Async\SluggableEntitiesProcessor;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SluggableEntitiesProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrine;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $producer;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var MessageFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageFactory;

    /**
     * @var SluggableEntitiesProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->jobRunner = new JobRunner();
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);

        $this->processor = new SluggableEntitiesProcessor(
            $this->doctrine,
            $this->jobRunner,
            $this->producer,
            $this->logger,
            $this->messageFactory
        );
    }

    public function testProcessRejectMessageWithNotManagableClass()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(sprintf('Entity manager is not defined for class: "%s"', $messageData));

        $this->assertEquals(SluggableEntitiesProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $messageData = \stdClass::class;
        $messageBody = json_encode($messageData);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session **/
        $session = $this->createMock(SessionInterface::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $classMetadata */
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with($messageData)
            ->willReturn($classMetadata);

        $countQb = $this->assertCountQueryCalled();
        $idsQb = $this->assertIdsQueryCalled();

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $countQb,
                $idsQb
            );
        $em->expects($this->once())
            ->method('getRepository')
            ->with($messageData)
            ->willReturn($repository);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($messageData)
            ->willReturn($em);

        $this->messageFactory->expects($this->once())
            ->method('createMassMessage')
            ->with($messageData, [42])
            ->willReturn(['className' => $messageData, 'id' => [42]]);
        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                Topics::JOB_GENERATE_DIRECT_URL_FOR_ENTITIES,
                ['className' => $messageData, 'id' => [42], 'jobId' => null]
            );

        $this->assertEquals(SluggableEntitiesProcessor::ACK, $this->processor->process($message, $session));
    }

    /**
     * @return QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertCountQueryCalled()
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $countQb */
        $countQb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $countQb->expects($this->any())
            ->method('select')
            ->willReturnSelf();
        $countQuery = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSingleScalarResult'])
            ->getMockForAbstractClass();
        $countQuery->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);
        $countQb->expects($this->once())
            ->method('getQuery')
            ->willReturn($countQuery);

        return $countQb;
    }

    /**
     * @return QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertIdsQueryCalled()
    {
        $idsQuery = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $idsQuery->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([['id' => 42]]);

        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $idsQb */
        $idsQb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $idsQb->expects($this->any())
            ->method('select')
            ->with('ids.id')
            ->willReturnSelf();
        $idsQb->expects($this->any())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf();
        $idsQb->expects($this->any())
            ->method('setMaxResults')
            ->with(1000)
            ->willReturnSelf();
        $idsQb->expects($this->any())
            ->method('orderBy')
            ->with('ids.id', 'ASC')
            ->willReturnSelf();
        $idsQb->expects($this->any())
            ->method('getQuery')
            ->willReturn($idsQuery);

        return $idsQb;
    }
}
