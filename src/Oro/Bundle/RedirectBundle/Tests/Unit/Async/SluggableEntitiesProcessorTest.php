<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\SluggableEntitiesProcessor;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Bundle\RedirectBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Test\JobRunner as TestJobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SluggableEntitiesProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrine;

    /**
     * @var JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $producer;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var SluggableEntitiesProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->jobRunner = $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $this->processor->addTopicMapping('test_topic_mass', 'test_topic');
    }

    /**
     * @dataProvider batchSizeDataProvider
     * @param int|float $batchSize
     * @param int $expected
     */
    public function testBatchSize($batchSize, $expected)
    {
        $this->processor->setBatchSize($batchSize);
        $property = new \ReflectionProperty(SluggableEntitiesProcessor::class, 'batchSize');
        $property->setAccessible(true);
        static::assertSame($expected, $property->getValue($this->processor));
    }

    /**
     * @return array
     */
    public function batchSizeDataProvider()
    {
        return [
            'correct value' => [1, 1],
            'negative value' => [-1, 1000],
            'zero value' => [0, 1000],
            'float incorrect value' => [-10.5, 1000],
            'float correct value' => [10.8, 10],
        ];
    }

    public function testProcessRejectMessageWithNotManagableClass()
    {
        $class = \stdClass::class;
        $createRedirect = true;

        $message = $this->assertMessageDataCalls($class, $createRedirect);
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(sprintf('Entity manager is not defined for class: "%s"', $class));

        $this->jobRunner->expects($this->never())
            ->method('runUnique');

        $this->assertEquals(SluggableEntitiesProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessInvalidMessage()
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('test_topic_mass');
        $messageData = null;
        $messageBody = json_encode($messageData);

        $e = new InvalidArgumentException('Invalid message');
        $this->messageFactory->expects($this->any())
            ->method('getEntityClassFromMessage')
            ->with($messageData)
            ->willThrowException($e);

        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        $session = $this->createMock(SessionInterface::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Queue Message is invalid',
                [
                    'topic' => 'test_topic_mass',
                    'exception' => $e
                ]
            );

        $this->jobRunner->expects($this->never())
            ->method('runUnique');

        $this->assertEquals(SluggableEntitiesProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        $class = \stdClass::class;
        $createRedirect = true;

        $message = $this->assertMessageDataCalls($class, $createRedirect);
        $message->expects($this->once())
            ->method('getMessageId')
            ->willReturn('mid-42');
        $message->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('test_topic_mass');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $countQb = $this->assertCountQueryCalled();
        $idsQb = $this->assertIdsQueryCalled();

        $repository = $this->configureRepositoryCalls($class);
        $repository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $countQb,
                $idsQb
            );

        $this->messageFactory->expects($this->once())
            ->method('createMassMessage')
            ->with($class, [42])
            ->willReturn(['className' => $class, 'id' => [42]]);
        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                'test_topic',
                ['className' => $class, 'id' => [42], 'jobId' => 123]
            );

        /** @var Job|\PHPUnit\Framework\MockObject\MockObject $job */
        $job = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Job|\PHPUnit\Framework\MockObject\MockObject $job */
        $childJob = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();
        $childJob->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->willReturnCallback(
                function ($ownerId, $name, $closure) use ($class, $job) {
                    $this->assertEquals('mid-42', $ownerId);
                    $this->assertEquals('test_topic_mass:' . $class, $name);

                    return $closure($this->jobRunner, $job);
                }
            );
        $this->jobRunner->expects($this->once())
            ->method('createDelayed')
            ->willReturnCallback(
                function ($name, $closure) use ($class, $childJob) {
                    $this->assertEquals(
                        sprintf('test_topic_mass:%s:%s', $class, 0),
                        $name
                    );

                    return $closure($this->jobRunner, $childJob);
                }
            );

        $this->assertEquals(SluggableEntitiesProcessor::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWithChangedBatchSize()
    {
        $class = \stdClass::class;
        $createRedirect = true;
        $message = $this->assertMessageDataCalls($class, $createRedirect);
        $message->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('test_topic_mass');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session * */
        $session = $this->createMock(SessionInterface::class);

        $countQb = $this->assertCountQueryCalled(5);

        $idsQuery = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $idsQuery->expects($this->exactly(2))
            ->method('getArrayResult')
            ->willReturnOnConsecutiveCalls(
                [['id' => 1], ['id' => 2], ['id' => 3]],
                [['id' => 4], ['id' => 5]]
            );

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $idsQb */
        $idsQb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $idsQb->expects($this->any())
            ->method('select')
            ->with('ids.id')
            ->willReturnSelf();
        $idsQb->expects($this->any())
            ->method('orderBy')
            ->with('ids.id', 'ASC')
            ->willReturnSelf();
        $idsQb->expects($this->any())
            ->method('setFirstResult')
            ->withConsecutive([0], [3])
            ->willReturnSelf();
        $idsQb->expects($this->any())
            ->method('setMaxResults')
            ->with(3)
            ->willReturnSelf();
        $idsQb->expects($this->any())
            ->method('getQuery')
            ->willReturn($idsQuery);

        $repository = $this->configureRepositoryCalls($class);
        $repository->expects($this->exactly(3))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $countQb,
                $idsQb,
                $idsQb
            );

        $this->messageFactory->expects($this->exactly(2))
            ->method('createMassMessage')
            ->withConsecutive(
                [$class, [1, 2, 3]],
                [$class, [4, 5]]
            )
            ->willReturnOnConsecutiveCalls(
                ['className' => $class, 'id' => [1, 2, 3]],
                ['className' => $class, 'id' => [4, 5]]
            );
        $this->producer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    'test_topic',
                    ['className' => $class, 'id' => [1, 2, 3], 'jobId' => null]
                ],
                [
                    'test_topic',
                    ['className' => $class, 'id' => [4, 5], 'jobId' => null]
                ]
            );

        $this->processor = new SluggableEntitiesProcessor(
            $this->doctrine,
            new TestJobRunner(),
            $this->producer,
            $this->logger,
            $this->messageFactory
        );
        $this->processor->addTopicMapping('test_topic_mass', 'test_topic');

        $this->processor->setBatchSize(3);
        $this->assertEquals(SluggableEntitiesProcessor::ACK, $this->processor->process($message, $session));
    }

    /**
     * @param int $count
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function assertCountQueryCalled($count = 1)
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $countQb */
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
            ->willReturn($count);
        $countQb->expects($this->once())
            ->method('getQuery')
            ->willReturn($countQuery);

        return $countQb;
    }

    /**
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
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

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $idsQb */
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

    /**
     * @param string $class
     * @param bool $createRedirect
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertMessageDataCalls($class, $createRedirect)
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message * */
        $message = $this->createMock(MessageInterface::class);
        $messageData = [
            DirectUrlMessageFactory::class => $class,
            DirectUrlMessageFactory::CREATE_REDIRECT => $createRedirect
        ];

        $messageBody = json_encode($messageData);

        $this->messageFactory->expects($this->any())
            ->method('getEntityClassFromMessage')
            ->with($messageData)
            ->willReturn($class);
        $this->messageFactory->expects($this->any())
            ->method('getCreateRedirectFromMessage')
            ->with($messageData)
            ->willReturn($createRedirect);

        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        return $message;
    }

    /**
     * @param string $class
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function configureRepositoryCalls($class)
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $classMetadata */
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn($classMetadata);

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->with($class)
            ->willReturn($repository);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn($em);

        return $repository;
    }
}
