<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapProcessor;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapByWebsiteAndTypeTopic;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapIndexTopic;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapTopic;
use Oro\Bundle\SEOBundle\Provider\WebsiteForSitemapProviderInterface;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Website\WebsiteUrlProvidersServiceInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Psr\Log\LoggerInterface;

class GenerateSitemapProcessorTest extends \PHPUnit\Framework\TestCase
{
    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private DependentJobService|\PHPUnit\Framework\MockObject\MockObject $dependentJob;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $producer;

    private WebsiteUrlProvidersServiceInterface|\PHPUnit\Framework\MockObject\MockObject $websiteUrlProvidersService;

    private WebsiteForSitemapProviderInterface|\PHPUnit\Framework\MockObject\MockObject $websiteProvider;

    private PublicSitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject $fileSystemAdapter;

    private CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject $canonicalUrlGenerator;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private GenerateSitemapProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->dependentJob = $this->createMock(DependentJobService::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->websiteUrlProvidersService = $this->createMock(WebsiteUrlProvidersServiceInterface::class);
        $this->websiteProvider = $this->createMock(WebsiteForSitemapProviderInterface::class);
        $this->fileSystemAdapter = $this->createMock(PublicSitemapFilesystemAdapter::class);
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new GenerateSitemapProcessor(
            $this->jobRunner,
            $this->dependentJob,
            $this->producer,
            $this->websiteUrlProvidersService,
            $this->websiteProvider,
            $this->fileSystemAdapter,
            $this->canonicalUrlGenerator,
            $this->logger
        );
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    private function getMessage(string $messageId): MessageInterface
    {
        $message = new Message();
        $message->setMessageId($messageId);

        return $message;
    }

    private function getWebsite(int $websiteId): Website
    {
        $website = $this->createMock(Website::class);
        $website->expects(self::any())
            ->method('getId')
            ->willReturn($websiteId);

        return $website;
    }

    private function getJobAndRunUnique($message): Job
    {
        $rootJob = new Job();
        $rootJob->setId(100);
        $job = new Job();
        $job->setId(200);
        $job->setRootJob($rootJob);

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        return $job;
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [GenerateSitemapTopic::getName()],
            GenerateSitemapProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenSendingSitemapGenerationMessageFailed(): void
    {
        $messageId = '1000';
        $message = $this->getMessage($messageId);
        $website = $this->getWebsite(123);

        $this->fileSystemAdapter->expects(self::once())
            ->method('clearTempStorage');

        $this->websiteProvider->expects(self::once())
            ->method('getAvailableWebsites')
            ->willReturn([$website]);

        $job = $this->getJobAndRunUnique($message);

        $dependentJobContext = new DependentJobContext($job->getRootJob());
        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with(self::identicalTo($job->getRootJob()))
            ->willReturn($dependentJobContext);
        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with(self::identicalTo($dependentJobContext));

        $exception = new \Exception('some error');
        $this->canonicalUrlGenerator->expects(self::once())
            ->method('clearCache');
        $this->websiteUrlProvidersService->expects(self::once())
            ->method('getWebsiteProvidersIndexedByNames')
            ->willReturn(['test_type' => $this->createMock(UrlItemsProviderInterface::class)]);
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function (string $name, \Closure $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });
        $this->producer->expects(self::once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating a sitemap.',
                ['exception' => $exception]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcess(): void
    {
        $messageId = '1000';
        $message = $this->getMessage($messageId);
        $websites = [
            $this->getWebsite(123),
            $this->getWebsite(234),
        ];

        $this->fileSystemAdapter->expects(self::once())
            ->method('clearTempStorage');

        $this->websiteProvider->expects(self::once())
            ->method('getAvailableWebsites')
            ->willReturn($websites);

        $job = $this->getJobAndRunUnique($message);

        $dependentJobContext = new DependentJobContext($job->getRootJob());
        $this->dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with(self::identicalTo($job->getRootJob()))
            ->willReturn($dependentJobContext);
        $this->dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with(self::identicalTo($dependentJobContext))
            ->willReturnCallback(function (DependentJobContext $context) use ($job) {
                $dependentJobs = $context->getDependentJobs();
                self::assertCount(1, $dependentJobs);
                self::assertEquals(GenerateSitemapIndexTopic::getName(), $dependentJobs[0]['topic']);
                self::assertEquals($job->getId(), $dependentJobs[0]['message']['jobId']);
                self::assertGreaterThan(0, $dependentJobs[0]['message']['version']);
                self::assertEquals([123, 234], $dependentJobs[0]['message']['websiteIds']);
            });

        $this->websiteUrlProvidersService->expects(self::exactly(2))
            ->method('getWebsiteProvidersIndexedByNames')
            ->willReturn([
                'first_type' => $this->createMock(UrlItemsProviderInterface::class),
                'second_type' => $this->createMock(UrlItemsProviderInterface::class),
            ]);
        $providerNames = ['first_type', 'second_type'];

        $this->canonicalUrlGenerator->expects(self::exactly(count($websites)))
            ->method('clearCache')
            ->withConsecutive([$websites[0]], [$websites[0]]);
        $jobNameTemplate = GenerateSitemapByWebsiteAndTypeTopic::getName() . ':%s:%s';
        $this->jobRunner->expects(self::exactly(count($providerNames) * count($websites)))
            ->method('createDelayed')
            ->withConsecutive(
                [sprintf($jobNameTemplate, $websites[0]->getId(), $providerNames[0])],
                [sprintf($jobNameTemplate, $websites[0]->getId(), $providerNames[1])],
                [sprintf($jobNameTemplate, $websites[1]->getId(), $providerNames[0])],
                [sprintf($jobNameTemplate, $websites[1]->getId(), $providerNames[1])]
            )
            ->willReturnCallback(function (string $name, \Closure $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });
        $this->producer->expects(self::exactly(count($providerNames) * count($websites)))
            ->method('send')
            ->with(GenerateSitemapByWebsiteAndTypeTopic::getName());

        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
