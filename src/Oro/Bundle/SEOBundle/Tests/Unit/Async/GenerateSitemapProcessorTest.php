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
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistry;
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

class GenerateSitemapProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var JobRunner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jobRunner;

    /**
     * @var DependentJobService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dependentJobService;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $producer;

    /**
     * @var UrlItemsProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $providerRegistry;

    /**
     * @var WebsiteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteProvider;

    /**
     * @var SitemapIndexMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexMessageFactory;

    /**
     * @var SitemapMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageFactory;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var GenerateSitemapProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->jobRunner = $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dependentJobService = $this->getMockBuilder(DependentJobService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->providerRegistry = $this->createMock(UrlItemsProviderRegistry::class);
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
            $this->providerRegistry,
            $this->websiteProvider,
            $this->indexMessageFactory,
            $this->messageFactory,
            $this->logger,
            $this->canonicalUrlGenerator
        );
    }

    public function testProcessWhenThrowsInvalidArgumentException()
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $messageId = 777;
        $data = ['key' => 'value'];
        $message = $this->getMessage($messageId, $data);
        $website = $this->getWebsite();
        $providerNames = $this->getProviderNames();
        $job = $this->getJobAndRunUnique($messageId);
        $this->expectCreateDelayed([$website], $providerNames);

        $dependentJobContext = new DependentJobContext($job);
        $this->dependentJobService->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);

        $exception = new InvalidArgumentException();
        $this->indexMessageFactory->expects($this->once())
            ->method('createMessage')
            ->with($website, $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT))
            ->willThrowException($exception);
        $this->dependentJobService->expects($this->never())
            ->method('saveDependentJob');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Queue Message is invalid',
                [
                    'exception' => $exception,
                    'message' => JSON::encode($data),
                ]
            );

        $this->assertEquals(UrlCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessWhenThrowsException()
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */

        $messageId = 777;
        $data = ['key' => 'value'];
        $message = $this->getMessage($messageId, $data);
        $website = $this->getWebsite();
        $providerNames = $this->getProviderNames();
        $job = $this->getJobAndRunUnique($messageId);
        $this->expectCreateDelayed([$website], $providerNames);

        $dependentJobContext = new DependentJobContext($job);
        $this->dependentJobService->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->indexMessageFactory->expects($this->once())
            ->method('createMessage')
            ->with($website, $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT))
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
                    'message' => JSON::encode($data),
                    'topic' => Topics::GENERATE_SITEMAP,
                ]
            );

        $this->assertEquals(UrlCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $messageId = 777;
        $message = $this->getMessage($messageId);
        $websites = $this->getWebsites();
        $providerNames = $this->getProviderNames();
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
            ->with($websites[0], $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT))
            ->willReturn(['some data']);
        $this->indexMessageFactory->expects($this->at(1))
            ->method('createMessage')
            ->with($websites[1], $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT))
            ->willReturn(['some data']);
        $this->dependentJobService->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

        $this->assertEquals(UrlCacheProcessor::ACK, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::GENERATE_SITEMAP],
            GenerateSitemapProcessor::getSubscribedTopics()
        );
    }

    /**
     * @return WebsiteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getWebsite()
    {
        $website = $this->createWebsiteMock(777);

        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$website]);

        return $website;
    }

    /**
     * @return WebsiteInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    private function getWebsites()
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
     * @return WebsiteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createWebsiteMock($websiteId)
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
    private function getJobAndRunUnique($messageId)
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
     * @return array
     */
    private function getProviderNames()
    {
        $providerNames = ['first', 'second'];
        $this->providerRegistry->expects($this->once())
            ->method('getProviderNames')
            ->willReturn($providerNames);

        return $providerNames;
    }

    /**
     * @param array|WebsiteInterface[] $websites
     * @param array $providerNames
     */
    private function expectCreateDelayed(array $websites, array $providerNames)
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
     * @return MessageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMessage($messageId, array $data = [])
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
