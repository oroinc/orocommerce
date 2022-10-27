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
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Test\JobRunner as TestJobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ReflectionUtil;

class SluggableEntitiesProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $producer;

    private MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $messageFactory;

    private SluggableEntitiesProcessor $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->createMock(MessageFactoryInterface::class);

        $this->processor = new SluggableEntitiesProcessor(
            $this->doctrine,
            $this->jobRunner,
            $this->producer,
            $this->messageFactory,
            'test_topic'
        );
        $this->setUpLoggerMock($this->processor);
    }

    /**
     * @dataProvider batchSizeDataProvider
     * @param int|float $batchSize
     * @param int $expected
     */
    public function testBatchSize($batchSize, $expected): void
    {
        $this->processor->setBatchSize($batchSize);
        self::assertSame($expected, ReflectionUtil::getPropertyValue($this->processor, 'batchSize'));
    }

    public function batchSizeDataProvider(): array
    {
        return [
            'correct value' => [1, 1],
            'negative value' => [-1, 1000],
            'zero value' => [0, 1000],
            'float incorrect value' => [-10.5, 1000],
            'float correct value' => [10.8, 10],
        ];
    }

    public function testProcess(): void
    {
        $class = \stdClass::class;
        $createRedirect = true;

        $message = $this->assertMessageDataCalls($class, $createRedirect);
        $message->expects(self::once())
            ->method('getMessageId')
            ->willReturn('mid-42');
        $message->expects(self::once())
            ->method('getProperty')
            ->with(MessageQueueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('test_topic_mass');

        $session = $this->createMock(SessionInterface::class);

        $countQb = $this->assertCountQueryCalled();
        $idsQb = $this->assertIdsQueryCalled();

        $repository = $this->configureRepositoryCalls($class);
        $repository->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($countQb, $idsQb);

        $this->messageFactory->expects(self::once())
            ->method('createMassMessage')
            ->with($class, [42])
            ->willReturn(['className' => $class, 'id' => [42]]);
        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                'test_topic',
                ['className' => $class, 'id' => [42], 'jobId' => 123]
            );

        $job = $this->createMock(Job::class);
        $childJob = $this->createMock(Job::class);
        $childJob->expects(self::once())
            ->method('getId')
            ->willReturn(123);
        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->willReturnCallback(function ($ownerId, $name, $closure) use ($class, $job) {
                $this->assertEquals('mid-42', $ownerId);
                $this->assertEquals('test_topic_mass:' . $class, $name);

                return $closure($this->jobRunner, $job);
            });
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($class, $childJob) {
                $this->assertEquals(
                    sprintf('test_topic_mass:%s:%s', $class, 0),
                    $name
                );

                return $closure($this->jobRunner, $childJob);
            });

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWithChangedBatchSize(): void
    {
        $class = \stdClass::class;
        $createRedirect = true;
        $message = $this->assertMessageDataCalls($class, $createRedirect);
        $message->expects(self::once())
            ->method('getProperty')
            ->with(MessageQueueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('test_topic_mass');

        $session = $this->createMock(SessionInterface::class);

        $countQb = $this->assertCountQueryCalled(5);

        $idsQuery = $this->createMock(AbstractQuery::class);
        $idsQuery->expects(self::exactly(2))
            ->method('getArrayResult')
            ->willReturnOnConsecutiveCalls(
                [['id' => 1], ['id' => 2], ['id' => 3]],
                [['id' => 4], ['id' => 5]]
            );

        $idsQb = $this->createMock(QueryBuilder::class);
        $idsQb->expects(self::any())
            ->method('select')
            ->with('ids.id')
            ->willReturnSelf();
        $idsQb->expects(self::any())
            ->method('orderBy')
            ->with('ids.id', 'ASC')
            ->willReturnSelf();
        $idsQb->expects(self::any())
            ->method('setFirstResult')
            ->withConsecutive([0], [3])
            ->willReturnSelf();
        $idsQb->expects(self::any())
            ->method('setMaxResults')
            ->with(3)
            ->willReturnSelf();
        $idsQb->expects(self::any())
            ->method('getQuery')
            ->willReturn($idsQuery);

        $repository = $this->configureRepositoryCalls($class);
        $repository->expects(self::exactly(3))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($countQb, $idsQb, $idsQb);

        $this->messageFactory->expects(self::exactly(2))
            ->method('createMassMessage')
            ->withConsecutive(
                [$class, [1, 2, 3]],
                [$class, [4, 5]]
            )
            ->willReturnOnConsecutiveCalls(
                ['className' => $class, 'id' => [1, 2, 3]],
                ['className' => $class, 'id' => [4, 5]]
            );
        $this->producer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    'test_topic',
                    ['className' => $class, 'id' => [1, 2, 3], 'jobId' => null],
                ],
                [
                    'test_topic',
                    ['className' => $class, 'id' => [4, 5], 'jobId' => null],
                ]
            );

        $this->processor = new SluggableEntitiesProcessor(
            $this->doctrine,
            new TestJobRunner(),
            $this->producer,
            $this->messageFactory,
            'test_topic'
        );

        $this->processor->setBatchSize(3);
        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    private function assertCountQueryCalled(int $count = 1): QueryBuilder
    {
        $countQb = $this->createMock(QueryBuilder::class);
        $countQb->expects(self::any())
            ->method('select')
            ->willReturnSelf();
        $countQuery = $this->createMock(AbstractQuery::class);
        $countQuery->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn($count);
        $countQb->expects(self::once())
            ->method('getQuery')
            ->willReturn($countQuery);

        return $countQb;
    }

    /**
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertIdsQueryCalled()
    {
        $idsQuery = $this->createMock(AbstractQuery::class);
        $idsQuery->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([['id' => 42]]);

        $idsQb = $this->createMock(QueryBuilder::class);
        $idsQb->expects(self::any())
            ->method('select')
            ->with('ids.id')
            ->willReturnSelf();
        $idsQb->expects(self::any())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf();
        $idsQb->expects(self::any())
            ->method('setMaxResults')
            ->with(1000)
            ->willReturnSelf();
        $idsQb->expects(self::any())
            ->method('orderBy')
            ->with('ids.id', 'ASC')
            ->willReturnSelf();
        $idsQb->expects(self::any())
            ->method('getQuery')
            ->willReturn($idsQuery);

        return $idsQb;
    }

    /**
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertMessageDataCalls(string $class, bool $createRedirect)
    {
        $message = $this->createMock(MessageInterface::class);
        $messageBody = [
            DirectUrlMessageFactory::class => $class,
            DirectUrlMessageFactory::CREATE_REDIRECT => $createRedirect,
        ];

        $this->messageFactory->expects(self::any())
            ->method('getEntityClassFromMessage')
            ->with($messageBody)
            ->willReturn($class);
        $this->messageFactory->expects(self::any())
            ->method('getCreateRedirectFromMessage')
            ->with($messageBody)
            ->willReturn($createRedirect);

        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($messageBody);

        return $message;
    }

    /**
     * @return EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function configureRepositoryCalls(string $class)
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn($classMetadata);

        $repository = $this->createMock(EntityRepository::class);

        $em->expects(self::once())
            ->method('getRepository')
            ->with($class)
            ->willReturn($repository);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($class)
            ->willReturn($em);

        return $repository;
    }
}
