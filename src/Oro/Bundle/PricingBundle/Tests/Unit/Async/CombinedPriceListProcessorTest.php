<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\CombinedPriceListProcessor;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListAssociationsProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CombinedPriceListProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CombinedPriceListAssociationsProvider|MockObject
     */
    private $cplAssociationsProvider;

    /**
     * @var JobRunner|MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|MockObject
     */
    private $producer;

    /**
     * @var DependentJobService|MockObject
     */
    private $dependentJob;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var CombinedPriceListProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cplAssociationsProvider = $this->createMock(CombinedPriceListAssociationsProvider::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->processor = new CombinedPriceListProcessor(
            $this->cplAssociationsProvider,
            $this->producer,
            $this->jobRunner,
            $this->dependentJob,
            $this->doctrine
        );
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [MassRebuildCombinedPriceListsTopic::getName()],
            CombinedPriceListProcessor::getSubscribedTopics()
        );
    }

    public function testProcessUnexpectedException()
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn([
                'assignments' => [['force' => true, 'website' => null, 'customer' => null, 'customerGroup' => null]]
            ]);

        $associations = [
            [
                'collection' => [new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 10]), false)],
                'assign_to' => ['config' => true]
            ]
        ];
        $this->cplAssociationsProvider->expects($this->once())
            ->method('getCombinedPriceListsWithAssociations')
            ->willReturn($associations);

        $e = new \Exception();
        $this->jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->willThrowException($e);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Combined Price Lists build.',
                ['exception' => $e]
            );

        $this->assertEquals(
            $this->processor::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    /**
     * @dataProvider messageDataProvider
     */
    public function testProcess(array $body, ?Website $website, ?object $targetEntity, bool $isForce)
    {
        $this->assertReference();
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        $association = [
            'collection' => [new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 10]), false)],
            'assign_to' => ['config' => true]
        ];

        $this->cplAssociationsProvider->expects($this->once())
            ->method('getCombinedPriceListsWithAssociations')
            ->with($isForce, $website, $targetEntity)
            ->willReturn([$association]);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects($this->any())
            ->method('getId')
            ->willReturn(4242);
        $job = $this->createMock(Job::class);
        $job->expects($this->any())
            ->method('getRootJob')
            ->willReturn($rootJob);
        $childJob = $this->createMock(Job::class);
        $childJob->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $this->jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(
                function ($actualMessage, $closure) use ($job, $message) {
                    $this->assertSame($actualMessage, $message);

                    return $closure($this->jobRunner, $job);
                }
            );
        $this->jobRunner->expects($this->once())
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $closure) use ($childJob) {
                return $closure($this->jobRunner, $childJob);
            });

        $dependentContext = $this->createMock(DependentJobContext::class);
        $dependentContext->expects($this->once())
            ->method('addDependentJob')
            ->with(RunCombinedPriceListPostProcessingStepsTopic::getName(), ['relatedJobId' => 4242]);
        $this->dependentJob->expects($this->once())
            ->method('createDependentJobContext')
            ->with($rootJob)
            ->willReturn($dependentContext);
        $this->dependentJob->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(
                CombineSingleCombinedPriceListPricesTopic::getName(),
                $this->callback(function ($args) use ($association) {
                    $this->assertEquals($association['collection'], $args['collection']);
                    $this->assertEquals($association['assign_to'], $args['assign_to']);
                    $this->assertEquals(42, $args['jobId']);
                    $this->assertIsInt($args['version']);

                    return true;
                })
            );

        $this->assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function messageDataProvider(): \Generator
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        $customer = $this->getEntity(Customer::class, ['id' => 10]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 100]);


        yield 'full rebuild' => [
            [
                'assignments' => [['force' => true, 'website' => null, 'customer' => null, 'customerGroup' => null]]
            ],
            null,
            null,
            true
        ];

        yield 'per website' => [
            [
                'assignments' => [
                    ['force' => false, 'website' => $website, 'customer' => null, 'customerGroup' => null]
                ]
            ],
            $website,
            null,
            false
        ];

        yield 'per website2' => [
            [
                'assignments' => [
                    ['force' => false, 'website' => $website2, 'customer' => null, 'customerGroup' => null]
                ]
            ],
            $website2,
            null,
            false
        ];

        yield 'per website and customer' => [
            [
                'assignments' => [
                    ['force' => false, 'website' => $website, 'customer' => $customer, 'customerGroup' => null]
                ]
            ],
            $website,
            $customer,
            false
        ];

        yield 'per website and customer group' => [
            [
                'assignments' => [
                    ['force' => false, 'website' => $website, 'customer' => null, 'customerGroup' => $customerGroup]
                ]
            ],
            $website,
            $customerGroup,
            false
        ];
    }

    private function assertReference()
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager
            ->expects($this->any())
            ->method('getReference')
            ->willReturnCallback(function ($className, $value) {
                return $this->getEntity($className, ['id' => is_object($value) ? $value->getId() : $value]);
            });
    }
}
