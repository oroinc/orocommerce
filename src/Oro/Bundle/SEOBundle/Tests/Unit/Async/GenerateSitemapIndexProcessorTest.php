<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapIndexProcessor;
use Oro\Bundle\SEOBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class GenerateSitemapIndexProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var DependentJobService|\PHPUnit\Framework\MockObject\MockObject */
    private $dependentJob;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var GenerateSitemapIndexProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new GenerateSitemapIndexProcessor(
            $this->jobRunner,
            $this->dependentJob,
            $this->producer,
            $this->logger
        );
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    private function getMessage(string $messageId, array $body): MessageInterface
    {
        $message = new Message();
        $message->setMessageId($messageId);
        $message->setBody(JSON::encode($body));

        return $message;
    }

    private function getJobAndRunUnique(string $messageId): Job
    {
        $rootJob = new Job();
        $rootJob->setId(100);
        $job = new Job();
        $job->setId(200);
        $job->setRootJob($rootJob);

        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->with($messageId, Topics::GENERATE_SITEMAP . ':index')
            ->willReturnCallback(function ($jobId, $name, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        return $job;
    }

    public function testGetSubscribedTopics()
    {
        self::assertEquals(
            [Topics::GENERATE_SITEMAP_INDEX],
            GenerateSitemapIndexProcessor::getSubscribedTopics()
        );
    }

    public function testProcessForWrongParameters()
    {
        $message = $this->getMessage('1000', ['key' => 'value']);

        $exception = new UndefinedOptionsException(
            'The option "key" does not exist. Defined options are: "jobId", "version", "websiteIds".'
        );
        $this->logger->expects(self::once())
            ->method('critical')
            ->with(
                'Got invalid message.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessForWrongJobIdParameter()
    {
        $message = $this->getMessage('1000', [
            'jobId'      => 'wrong',
            'version'    => 1,
            'websiteIds' => [123]
        ]);

        $exception = new InvalidOptionsException(
            'The option "jobId" with value "wrong" is expected to be of type "int", but is of type "string".'
        );
        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.', ['exception' => $exception]);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessForWrongVersionParameter()
    {
        $message = $this->getMessage('1000', [
            'jobId'      => 100,
            'version'    => 'wrong',
            'websiteIds' => [123]
        ]);

        $exception = new InvalidOptionsException(
            'The option "version" with value "wrong" is expected to be of type "int", but is of type "string".'
        );
        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.', ['exception' => $exception]);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessForWrongWebsiteIdsParameter()
    {
        $message = $this->getMessage('1000', [
            'jobId'      => 100,
            'version'    => 1,
            'websiteIds' => 123
        ]);

        $exception = new InvalidOptionsException(
            'The option "websiteIds" with value 123 is expected to be of type "array", but is of type "integer".'
        );
        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.', ['exception' => $exception]);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessWhenSendChildMessagesFailed()
    {
        $jobId = 100;
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'jobId'      => $jobId,
            'version'    => 1,
            'websiteIds' => [123]
        ]);

        $job = $this->getJobAndRunUnique($messageId);

        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function (string $name, \Closure $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $exception = new \Exception();
        $this->producer->expects(self::once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating sitemap indexes.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcess()
    {
        $jobId = 100;
        $version = 1;
        $websiteIds = [123, 234];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'jobId'      => $jobId,
            'version'    => $version,
            'websiteIds' => $websiteIds
        ]);

        $job = $this->getJobAndRunUnique($messageId);

        $dependentJobContext = new DependentJobContext($job->getRootJob());
        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with(self::identicalTo($job->getRootJob()))
            ->willReturn($dependentJobContext);
        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with(self::identicalTo($dependentJobContext))
            ->willReturnCallback(function (DependentJobContext $context) use ($version, $websiteIds) {
                self::assertEquals(
                    [
                        [
                            'topic'    => Topics::GENERATE_SITEMAP_MOVE_GENERATED_FILES,
                            'message'  => ['version' => $version, 'websiteIds' => $websiteIds],
                            'priority' => null,
                        ]
                    ],
                    $context->getDependentJobs()
                );
            });

        $this->jobRunner->expects(self::exactly(2))
            ->method('createDelayed')
            ->withConsecutive(
                [Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE . ':' . $websiteIds[0] . ':' . $version],
                [Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE . ':' . $websiteIds[1] . ':' . $version]
            )
            ->willReturnCallback(function (string $name, \Closure $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });
        $this->producer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE,
                    ['jobId' => $job->getId(), 'version' => $version, 'websiteId' => $websiteIds[0]]
                ],
                [
                    Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE,
                    ['jobId' => $job->getId(), 'version' => $version, 'websiteId' => $websiteIds[1]]
                ]
            );

        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
