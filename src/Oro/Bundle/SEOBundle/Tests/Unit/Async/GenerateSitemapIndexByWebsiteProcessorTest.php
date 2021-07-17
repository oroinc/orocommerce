<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapIndexByWebsiteProcessor;
use Oro\Bundle\SEOBundle\Async\Topics;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class GenerateSitemapIndexByWebsiteProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var SitemapDumperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $sitemapDumper;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var GenerateSitemapIndexByWebsiteProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->sitemapDumper = $this->createMock(SitemapDumperInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new GenerateSitemapIndexByWebsiteProcessor(
            $this->jobRunner,
            $this->doctrine,
            $this->sitemapDumper,
            $this->logger
        );
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = new Message();
        $message->setBody(JSON::encode($body));

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

    public function testGetSubscribedTopics()
    {
        self::assertEquals(
            [Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE],
            GenerateSitemapIndexByWebsiteProcessor::getSubscribedTopics()
        );
    }

    public function testProcessForWrongParameters()
    {
        $message = $this->getMessage(['key' => 'value']);

        $exception = new UndefinedOptionsException(
            'The option "key" does not exist. Defined options are: "jobId", "version", "websiteId".'
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
        $message = $this->getMessage([
            'jobId'     => 'wrong',
            'version'   => 1,
            'websiteId' => 123
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
        $message = $this->getMessage([
            'jobId'     => 100,
            'version'   => 'wrong',
            'websiteId' => 123
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

    public function testProcessForWrongWebsiteIdParameter()
    {
        $message = $this->getMessage([
            'jobId'     => 100,
            'version'   => 1,
            'websiteId' => 'wrong'
        ]);

        $exception = new InvalidOptionsException(
            'The option "websiteId" with value "wrong" is expected to be of type "int", but is of type "string".'
        );
        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.', ['exception' => $exception]);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessWhenDumpFailed()
    {
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

        $website = $this->getWebsite($websiteId);
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
        $this->sitemapDumper->expects(self::once())
            ->method('dump')
            ->with($website, $version, 'index')
            ->willThrowException($exception);
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating a sitemap index for a website.',
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

        $website = $this->getWebsite($websiteId);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, $websiteId)
            ->willReturn($website);

        $this->sitemapDumper->expects(self::once())
            ->method('dump')
            ->with(self::identicalTo($website), $version, 'index');

        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
