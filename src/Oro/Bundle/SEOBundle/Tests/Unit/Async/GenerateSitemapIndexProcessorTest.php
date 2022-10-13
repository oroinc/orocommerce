<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapIndexProcessor;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapIndexTopic;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\SEO\Tools\SitemapDumperInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class GenerateSitemapIndexProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private SitemapDumperInterface|\PHPUnit\Framework\MockObject\MockObject $sitemapDumper;

    private PublicSitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject $fileSystemAdapter;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private WebsiteManager|\PHPUnit\Framework\MockObject\MockObject $websiteManager;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private GenerateSitemapIndexProcessor $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->sitemapDumper = $this->createMock(SitemapDumperInterface::class);
        $this->fileSystemAdapter = $this->createMock(PublicSitemapFilesystemAdapter::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->processor = new GenerateSitemapIndexProcessor(
            $this->doctrine,
            $this->sitemapDumper,
            $this->fileSystemAdapter,
            $this->logger,
            $this->websiteManager,
            $this->configManager
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
        $message->setBody($body);

        return $message;
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [GenerateSitemapIndexTopic::getName()],
            GenerateSitemapIndexProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $version = 1;
        $websiteIds = [123];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'version' => $version,
            'websiteIds' => $websiteIds,
        ]);

        $website = $this->getEntity(Website::class, ['id' => 123]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, 123)
            ->willReturn($website);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $this->websiteManager
            ->expects($this->once())
            ->method('setCurrentWebsite')
            ->with($website);
        $this->configManager
            ->expects($this->once())
            ->method('setScopeId')
            ->with(123);

        $this->sitemapDumper->expects(self::once())
            ->method('dump')
            ->with($website, $version, 'index');
        $this->fileSystemAdapter->expects(self::once())
            ->method('moveSitemaps')
            ->with([123]);

        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessDumpFailed(): void
    {
        $version = 1;
        $websiteIds = [123];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'version' => $version,
            'websiteIds' => $websiteIds,
        ]);

        $website = $this->getEntity(Website::class, ['id' => 123]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, 123)
            ->willReturn($website);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $this->websiteManager
            ->expects($this->once())
            ->method('setCurrentWebsite')
            ->with($website);
        $this->configManager
            ->expects($this->once())
            ->method('setScopeId')
            ->with(123);

        $exception = new \Exception('Test');

        $this->sitemapDumper->expects(self::once())
            ->method('dump')
            ->with($website, $version, 'index')
            ->willThrowException($exception);
        $this->fileSystemAdapter->expects(self::never())
            ->method('moveSitemaps');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating a sitemap index for a website.',
                [
                    'websiteId' => 123,
                    'exception' => $exception,
                ]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessMoveFailed(): void
    {
        $version = 1;
        $websiteIds = [123];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'version' => $version,
            'websiteIds' => $websiteIds,
        ]);

        $website = $this->createMock(Website::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(Website::class, 123)
            ->willReturn($website);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $exception = new \Exception('Test');

        $this->sitemapDumper->expects(self::once())
            ->method('dump')
            ->with($website, $version, 'index');
        $this->fileSystemAdapter->expects(self::once())
            ->method('moveSitemaps')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during moving the generated sitemaps.',
                [
                    'websiteIds' => [123],
                    'exception' => $exception,
                ]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessDumpForMoreThanOneWebsiteOnlyOneFound(): void
    {
        $version = 1;
        $websiteIds = [1, 2];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'version' => $version,
            'websiteIds' => $websiteIds,
        ]);

        $website = $this->getEntity(Website::class, ['id' => 2]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))
            ->method('find')
            ->withConsecutive(
                [Website::class, 1],
                [Website::class, 2]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $website
            );
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $this->websiteManager
            ->expects($this->once())
            ->method('setCurrentWebsite')
            ->with($website);
        $this->configManager
            ->expects($this->once())
            ->method('setScopeId')
            ->with(2);

        $this->sitemapDumper->expects(self::once())
            ->method('dump')
            ->with($website, $version, 'index');
        $this->fileSystemAdapter->expects(self::once())
            ->method('moveSitemaps')
            ->with([2]);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('The website with 1 was not found during generating a sitemap index');

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessDumpForMoreThanOneWebsiteOnlyOneDumped(): void
    {
        $version = 1;
        $websiteIds = [1, 2];
        $messageId = '1000';
        $message = $this->getMessage($messageId, [
            'version' => $version,
            'websiteIds' => $websiteIds,
        ]);

        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))
            ->method('find')
            ->withConsecutive(
                [Website::class, 1],
                [Website::class, 2]
            )
            ->willReturnOnConsecutiveCalls(
                $website1,
                $website2
            );
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $this->websiteManager
            ->expects(self::exactly(2))
            ->method('setCurrentWebsite')
            ->withConsecutive([$website1], [$website2]);
        $this->configManager
            ->expects(self::exactly(2))
            ->method('setScopeId')
            ->withConsecutive([1], [2]);

        $exception = new \Exception('Test');
        $this->sitemapDumper->expects(self::exactly(2))
            ->method('dump')
            ->willReturnCallback(function ($ws) use ($website1, $exception) {
                if ($ws === $website1) {
                    throw $exception;
                }
            });
        $this->fileSystemAdapter->expects(self::once())
            ->method('moveSitemaps')
            ->with([2]);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during generating a sitemap index for a website.',
                [
                    'websiteId' => 1,
                    'exception' => $exception,
                ]
            );

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
