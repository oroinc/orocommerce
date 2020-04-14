<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\RedirectBundle\Async\UrlCacheProcessor;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapProcessor;
use Oro\Bundle\SEOBundle\Async\Topics;
use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SEOBundle\Model\SitemapIndexMessageFactory;
use Oro\Bundle\SEOBundle\Model\SitemapMessageFactory;
use Oro\Bundle\SEOBundle\Sitemap\Website\WebsiteUrlProvidersServiceInterface;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Website\WebsiteInterface;
use Psr\Log\LoggerInterface;

class GenerateSitemapProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRunner;

    /**
     * @var DependentJobService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dependentJobService;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $producer;

    /**
     * @var WebsiteUrlProvidersServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteUrlProvidersService;

    /**
     * @var WebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteProvider;

    /**
     * @var SitemapIndexMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $indexMessageFactory;

    /**
     * @var SitemapMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var GenerateSitemapProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dependentJobService = $this->getMockBuilder(DependentJobService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->websiteUrlProvidersService = $this->createMock(WebsiteUrlProvidersServiceInterface::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->messageFactory = $this->getMockBuilder(SitemapMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->indexMessageFactory = $this->getMockBuilder(SitemapIndexMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->canonicalUrlGenerator = $this->getMockBuilder(CanonicalUrlGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new GenerateSitemapProcessor(
            $this->jobRunner,
            $this->dependentJobService,
            $this->producer,
            $this->websiteUrlProvidersService,
            $this->websiteProvider,
            $this->indexMessageFactory,
            $this->messageFactory,
            $this->logger,
            $this->canonicalUrlGenerator
        );
    }

    public function testProcessWhenThrowsInvalidArgumentException(): void
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $messageId = 777;
        $data = ['key' => 'value'];
        $message = $this->getMessage($messageId, $data);
        $website = $this->getWebsite();
        $job = $this->getJobAndRunUnique($messageId);
        $this->jobRunner
            ->expects($this->never())
            ->method('createDelayed');
        $this->canonicalUrlGenerator
            ->expects($this->never())
            ->method('clearCache');

        $dependentJobContext = new DependentJobContext($job);
        $this->dependentJobService->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);

        $exception = new InvalidArgumentException();
        $this->indexMessageFactory->expects($this->once())
            ->method('createMessage')
            ->with($website, $this->isType(\PHPUnit\Framework\Constraint\IsType::TYPE_INT))
            ->willThrowException($exception);
        $this->dependentJobService->expects($this->never())
            ->method('saveDependentJob');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Queue Message is invalid',
                [
                    'exception' => $exception,
                ]
            );

        $this->assertEquals(UrlCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessWhenThrowsException(): void
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */

        $messageId = 777;
        $data = ['key' => 'value'];
        $message = $this->getMessage($messageId, $data);
        $website = $this->getWebsite();
        $job = $this->getJobAndRunUnique($messageId);
        $this->jobRunner
            ->expects($this->never())
            ->method('createDelayed');
        $this->canonicalUrlGenerator
            ->expects($this->never())
            ->method('clearCache');

        $dependentJobContext = new DependentJobContext($job);
        $this->dependentJobService->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->indexMessageFactory->expects($this->once())
            ->method('createMessage')
            ->with($website, $this->isType(\PHPUnit\Framework\Constraint\IsType::TYPE_INT))
            ->willReturn(['some data']);

        $exception = new \Exception();
        $this->dependentJobService->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentJobContext)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $exception,
                    'topic' => Topics::GENERATE_SITEMAP,
                ]
            );

        $this->assertEquals(UrlCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess(): void
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $messageId = 777;
        $message = $this->getMessage($messageId);
        $websites = $this->getWebsites();
        $providerNames = [];
        $providerNames = array_merge($providerNames, $this->getWebsiteProvidersByNames($websites[0]));
        $job = $this->getJobAndRunUnique($messageId);
        $this->expectCreateDelayed($websites, $providerNames);

        $dependentJobContext = new DependentJobContext($job);
        $this->dependentJobService->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->indexMessageFactory->expects($this->exactly(count($websites)))
            ->method('createMessage');
        $this->indexMessageFactory->expects($this->at(0))
            ->method('createMessage')
            ->with($websites[0], $this->isType(\PHPUnit\Framework\Constraint\IsType::TYPE_INT))
            ->willReturn(['some data']);
        $this->indexMessageFactory->expects($this->at(1))
            ->method('createMessage')
            ->with($websites[1], $this->isType(\PHPUnit\Framework\Constraint\IsType::TYPE_INT))
            ->willReturn(['some data']);
        $this->dependentJobService->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

        $this->assertEquals(UrlCacheProcessor::ACK, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics(): void
    {
        $this->assertEquals(
            [Topics::GENERATE_SITEMAP],
            GenerateSitemapProcessor::getSubscribedTopics()
        );
    }

    /**
     * @return WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getWebsite(): \PHPUnit\Framework\MockObject\MockObject
    {
        $website = $this->createWebsiteMock(777);

        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$website]);

        return $website;
    }

    /**
     * @return WebsiteInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    private function getWebsites(): array
    {
        $websites = [
            $this->createWebsiteMock(777),
            $this->createWebsiteMock(888),
        ];
        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);

        return $websites;
    }

    /**
     * @param int $websiteId
     * @return WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createWebsiteMock($websiteId): \PHPUnit\Framework\MockObject\MockObject
    {
        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        return $website;
    }

    /**
     * @param int $messageId
     * @return Job
     */
    private function getJobAndRunUnique($messageId): Job
    {
        $jobRunner = $this->jobRunner;

        /** @var Job $job */
        $job = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, ['id' => 2, 'rootJob' => $job]);
        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->with(
                $messageId,
                Topics::GENERATE_SITEMAP
            )
            ->willReturnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                return $callback($jobRunner, $childJob);
            });

        return $job;
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return array
     */
    private function getWebsiteProvidersByNames(WebsiteInterface $website): array
    {
        $providerNames = [
            'first' => '',
            'second' => ''
        ];
        $this->websiteUrlProvidersService->expects($this->any())
            ->method('getWebsiteProvidersIndexedByNames')
            ->with($website)
            ->willReturn($providerNames);

        return array_keys($providerNames);
    }

    /**
     * @param array|WebsiteInterface[] $websites
     * @param array $providerNames
     */
    private function expectCreateDelayed(array $websites, array $providerNames): void
    {
        $this->jobRunner->expects($this->exactly(count($providerNames)*count($websites)))
            ->method('createDelayed');
        $this->canonicalUrlGenerator->expects($this->exactly(count($websites)))
            ->method('clearCache');

        $count = 0;
        foreach ($websites as $key => $website) {
            $this->canonicalUrlGenerator->expects($this->at($key))
                ->method('clearCache')
                ->with($website);

            foreach ($providerNames as $providerName) {
                $this->jobRunner->expects($this->at($count))
                    ->method('createDelayed')
                    ->with(
                        sprintf(
                            '%s:%s:%s',
                            Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE,
                            $website->getId(),
                            $providerName
                        )
                    );
                $count++;
            }
        }
    }

    /**
     * @param int $messageId
     * @param array $data
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMessage($messageId, array $data = []): \PHPUnit\Framework\MockObject\MockObject
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($data));
        $message->expects($this->any())
            ->method('getMessageId')
            ->willReturn($messageId);

        return $message;
    }
}
