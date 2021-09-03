<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapIndexSingleThreadProcessor;
use Oro\Bundle\SEOBundle\Async\Topics;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class GenerateSitemapIndexSingleThreadProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var SitemapDumperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $sitemapDumper;

    /** @var PublicSitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $fileSystemAdapter;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var GenerateSitemapIndexSingleThreadProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->sitemapDumper = $this->createMock(SitemapDumperInterface::class);
        $this->fileSystemAdapter = $this->createMock(PublicSitemapFilesystemAdapter::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new GenerateSitemapIndexSingleThreadProcessor(
            $this->doctrine,
            $this->sitemapDumper,
            $this->fileSystemAdapter,
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

    public function testGetSubscribedTopics()
    {
        self::assertEquals(
            [Topics::GENERATE_SITEMAP_INDEX_ST],
            GenerateSitemapIndexSingleThreadProcessor::getSubscribedTopics()
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

    public function testProcessForWrongVersionParameter()
    {
        $message = $this->getMessage('1000', [
            'version' => 'wrong',
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
            'version' => 1,
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

    public function testProcess()
    {
        $version = 1;
        $websiteIds = [123];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'version' => $version,
            'websiteIds' => $websiteIds
        ]);

        $website = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Website::class, 123)
            ->willReturn($website);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $this->sitemapDumper->expects($this->once())
            ->method('dump')
            ->with($website, $version, 'index');
        $this->fileSystemAdapter->expects($this->once())
            ->method('moveSitemaps')
            ->with([123]);

        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessDumpFailed()
    {
        $version = 1;
        $websiteIds = [123];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'version' => $version,
            'websiteIds' => $websiteIds
        ]);

        $website = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Website::class, 123)
            ->willReturn($website);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $exception = new \Exception('Test');

        $this->sitemapDumper->expects($this->once())
            ->method('dump')
            ->with($website, $version, 'index')
            ->willThrowException($exception);
        $this->fileSystemAdapter->expects($this->never())
            ->method('moveSitemaps');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating a sitemap index for a website.',
                [
                    'websiteId' => 123,
                    'exception' => $exception
                ]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessMoveFailed()
    {
        $version = 1;
        $websiteIds = [123];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'version' => $version,
            'websiteIds' => $websiteIds
        ]);

        $website = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Website::class, 123)
            ->willReturn($website);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $exception = new \Exception('Test');

        $this->sitemapDumper->expects($this->once())
            ->method('dump')
            ->with($website, $version, 'index');
        $this->fileSystemAdapter->expects($this->once())
            ->method('moveSitemaps')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during moving the generated sitemaps.',
                [
                    'websiteIds' => [123],
                    'exception' => $exception
                ]
            );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessDumpForMoreThanOneWebsiteOnlyOneFound()
    {
        $version = 1;
        $websiteIds = [1, 2];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'version' => $version,
            'websiteIds' => $websiteIds
        ]);

        $website = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [Website::class, 1],
                [Website::class, 2]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $website
            );
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $this->sitemapDumper->expects($this->once())
            ->method('dump')
            ->with($website, $version, 'index');
        $this->fileSystemAdapter->expects($this->once())
            ->method('moveSitemaps')
            ->with([2]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('The website with 1 was not found during generating a sitemap index');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessDumpForMoreThanOneWebsiteOnlyOneDumped()
    {
        $version = 1;
        $websiteIds = [1, 2];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'jobId' => 1,
            'version' => $version,
            'websiteIds' => $websiteIds
        ]);

        $website1 = $this->createMock(Website::class);
        $website2 = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                [Website::class, 1],
                [Website::class, 2]
            )
            ->willReturnOnConsecutiveCalls(
                $website1,
                $website2
            );
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $exception = new \Exception('Test');
        $this->sitemapDumper->expects($this->exactly(2))
            ->method('dump')
            ->willReturnCallback(function ($ws) use ($website1, $exception) {
                if ($ws === $website1) {
                    throw $exception;
                }
            });
        $this->fileSystemAdapter->expects($this->once())
            ->method('moveSitemaps')
            ->with([2]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating a sitemap index for a website.',
                [
                    'websiteId' => 1,
                    'exception' => $exception
                ]
            );

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
