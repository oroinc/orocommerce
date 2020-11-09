<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\RedirectBundle\Async\UrlCacheProcessor;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapIndexProcessor;
use Oro\Bundle\SEOBundle\Async\Topics;
use Oro\Bundle\SEOBundle\Model\SitemapIndexMessageFactory;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Psr\Log\LoggerInterface;

class GenerateSitemapIndexProcessorTest extends \PHPUnit\Framework\TestCase
{
    const INDEX_FILE_PROVIDER_NAME = 'index';

    /**
     * @var JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRunner;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrine;

    /**
     * @var SitemapIndexMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var SitemapDumperInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dumper;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var GenerateSitemapIndexProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->messageFactory = $this->getMockBuilder(SitemapIndexMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dumper = $this->createMock(SitemapDumperInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new GenerateSitemapIndexProcessor(
            $this->messageFactory,
            $this->dumper,
            $this->logger
        );
        $this->processor->setJobRunner($this->jobRunner);
        $this->processor->setDoctrine($this->doctrine);
    }

    /**
     * @param array $body
     *
     * @return MessageInterface
     */
    private function getMessage(array $body): MessageInterface
    {
        $message = new NullMessage();
        $message->setBody(JSON::encode($body));

        return $message;
    }

    public function testProcessWhenThrowsException()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $jobId = 100;
        $version = 1;
        $websiteId = 123;
        $message = $this->getMessage([
            'jobId'     => $jobId,
            'version'   => $version,
            'websiteId' => $websiteId
        ]);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function (int $jobId, \Closure $callback) {
                return $callback($this->jobRunner, new Job());
            });

        $website = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        $exception = new \Exception();
        $this->dumper->expects($this->once())
            ->method('dump')
            ->with($website, $version, self::INDEX_FILE_PROVIDER_NAME)
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating a sitemap index for a website.',
                [
                    'exception' => $exception,
                ]
            );

        $this->assertEquals(UrlCacheProcessor::REJECT, $this->processor->process($message, $session));
    }

    public function testProcess()
    {
        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $jobId = 100;
        $version = 1;
        $websiteId = 123;
        $message = $this->getMessage([
            'jobId'     => $jobId,
            'version'   => $version,
            'websiteId' => $websiteId
        ]);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function (int $jobId, \Closure $callback) {
                return $callback($this->jobRunner, new Job());
            });

        $website = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        $this->dumper->expects($this->once())
            ->method('dump', self::INDEX_FILE_PROVIDER_NAME)
            ->with($website, $version);

        $this->assertEquals(UrlCacheProcessor::ACK, $this->processor->process($message, $session));
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE],
            GenerateSitemapIndexProcessor::getSubscribedTopics()
        );
    }
}
