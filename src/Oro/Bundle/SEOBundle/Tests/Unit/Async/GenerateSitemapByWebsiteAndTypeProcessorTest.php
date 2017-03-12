<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Oro\Bundle\SEOBundle\Async\GenerateSitemapByWebsiteAndTypeProcessor;
use Oro\Bundle\SEOBundle\Async\Topics;
use Oro\Bundle\SEOBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\SEOBundle\Model\SitemapMessageFactory;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Test\JobRunner as TestJobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Website\WebsiteInterface;
use Psr\Log\LoggerInterface;

class GenerateSitemapByWebsiteAndTypeProcessorTest extends \PHPUnit_Framework_TestCase
{
    const JOB_ID = 123;
    const WEBSITE_ID = 7;
    const TYPE = 'someType';
    const VERSION = 123;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * @var JobRunner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jobRunner;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var SitemapDumperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sitemapDumper;

    /**
     * @var SitemapMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageFactory;

    /**
     * @var GenerateSitemapByWebsiteAndTypeProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->session = $this->createMock(SessionInterface::class);

        $this->jobRunner = $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->sitemapDumper = $this->createMock(SitemapDumperInterface::class);
        $this->messageFactory = $this->getMockBuilder(SitemapMessageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new GenerateSitemapByWebsiteAndTypeProcessor(
            $this->jobRunner,
            $this->logger,
            $this->sitemapDumper,
            $this->messageFactory
        );
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE],
            GenerateSitemapByWebsiteAndTypeProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenThrowsInvalidArgumentException()
    {
        $messageBody = [
            SitemapMessageFactory::JOB_ID => self::JOB_ID,
            SitemapMessageFactory::WEBSITE_ID => self::WEBSITE_ID,
            SitemapMessageFactory::TYPE => self::TYPE
        ];

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));

        $exception = new InvalidArgumentException();
        $this->messageFactory->expects($this->once())
            ->method('getJobIdFromMessage')
            ->with($messageBody)
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Queue Message is invalid',
                [
                    'exception' => $exception,
                    'message' => JSON::encode($messageBody),
                ]
            );

        $this->createProcessorWithTestJobRunner();

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function testProcessWhenThrowsException()
    {
        $messageBody = [
            SitemapMessageFactory::JOB_ID => self::JOB_ID,
            SitemapMessageFactory::WEBSITE_ID => self::WEBSITE_ID,
            SitemapMessageFactory::TYPE => self::TYPE
        ];

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));

        $exception = new \Exception();
        $website = $this->createMock(WebsiteInterface::class);
        $this->assertMessageFactoryCalled($messageBody, $website);

        $this->sitemapDumper->expects($this->once())
            ->method('dump')
            ->with($website, self::VERSION, self::TYPE)
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $exception,
                    'message' => JSON::encode($messageBody),
                    'topic' => Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE,
                ]
            );

        $this->createProcessorWithTestJobRunner();

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function testProcess()
    {
        $messageBody = [
            SitemapMessageFactory::JOB_ID => self::JOB_ID,
            SitemapMessageFactory::WEBSITE_ID => self::WEBSITE_ID,
            SitemapMessageFactory::TYPE => self::TYPE
        ];

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));

        $website = $this->createMock(WebsiteInterface::class);
        $this->assertMessageFactoryCalled($messageBody, $website);

        $this->sitemapDumper->expects($this->once())
            ->method('dump')
            ->with($website, self::VERSION, self::TYPE);

        $this->createProcessorWithTestJobRunner();

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    private function createProcessorWithTestJobRunner()
    {
        $this->jobRunner = new TestJobRunner();

        $this->processor = new GenerateSitemapByWebsiteAndTypeProcessor(
            $this->jobRunner,
            $this->logger,
            $this->sitemapDumper,
            $this->messageFactory
        );
    }

    /**
     * @param array $messageBody
     * @param WebsiteInterface $website
     */
    private function assertMessageFactoryCalled(array $messageBody, WebsiteInterface $website = null)
    {
        $this->messageFactory->expects($this->once())
            ->method('getJobIdFromMessage')
            ->with($messageBody)
            ->willReturn(self::JOB_ID);
        $this->messageFactory->expects($this->once())
            ->method('getWebsiteFromMessage')
            ->with($messageBody)
            ->willReturn($website);
        $this->messageFactory->expects($this->once())
            ->method('getTypeFromMessage')
            ->with($messageBody)
            ->willReturn(self::TYPE);
        $this->messageFactory->expects($this->once())
            ->method('getVersionFromMessage')
            ->with($messageBody)
            ->willReturn(self::VERSION);
    }
}
