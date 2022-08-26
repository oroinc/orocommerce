<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Async;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapByWebsiteAndTypeProcessor;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapByWebsiteAndTypeTopic;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class GenerateSitemapByWebsiteAndTypeProcessorTest extends WebTestCase
{
    use JobsAwareTestTrait;
    use DefaultWebsiteIdTestTrait;

    private const PROVIDER = 'login_urls';

    private GenerateSitemapByWebsiteAndTypeProcessor $processor;

    private FileManager $tmpFileManager;

    protected function setUp(): void
    {
        $this->initClient();

        $this->processor = self::getContainer()
            ->get('oro_seo.async.generate_sitemap_by_website_and_type');
        $this->tmpFileManager = self::getContainer()
            ->get('oro_seo.file_manager.tmp_data');

        $this->tmpFileManager->deleteAllFiles();
    }

    protected function tearDown(): void
    {
        $this->tmpFileManager->deleteAllFiles();
    }

    public function testProcess(): void
    {
        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setBody(
            [
                GenerateSitemapByWebsiteAndTypeTopic::JOB_ID => $this->createUniqueJob()->getId(),
                GenerateSitemapByWebsiteAndTypeTopic::VERSION => time(),
                GenerateSitemapByWebsiteAndTypeTopic::WEBSITE_ID => $this->getDefaultWebsiteId(),
                GenerateSitemapByWebsiteAndTypeTopic::TYPE => self::PROVIDER,
            ]
        );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertSame(MessageProcessorInterface::ACK, $result);
        self::assertTrue($this->tmpFileManager->hasFile($this->getFileName()));
    }

    public function testProcessWithInvalidJobId(): void
    {
        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setBody(
            [
                GenerateSitemapByWebsiteAndTypeTopic::JOB_ID => 123456789,
                GenerateSitemapByWebsiteAndTypeTopic::VERSION => time(),
                GenerateSitemapByWebsiteAndTypeTopic::WEBSITE_ID => $this->getDefaultWebsiteId(),
                GenerateSitemapByWebsiteAndTypeTopic::TYPE => self::PROVIDER,
            ]
        );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertSame(MessageProcessorInterface::REJECT, $result);
        self::assertFalse($this->tmpFileManager->hasFile($this->getFileName()));
    }

    public function testProcessWithInvalidWebsiteId(): void
    {
        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setBody(
            [
                GenerateSitemapByWebsiteAndTypeTopic::JOB_ID => $this->createUniqueJob()->getId(),
                GenerateSitemapByWebsiteAndTypeTopic::VERSION => time(),
                GenerateSitemapByWebsiteAndTypeTopic::WEBSITE_ID => 123456789,
                GenerateSitemapByWebsiteAndTypeTopic::TYPE => self::PROVIDER,
            ]
        );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertSame(MessageProcessorInterface::REJECT, $result);
        self::assertFalse($this->tmpFileManager->hasFile($this->getFileName()));
    }

    private function getFileName(): string
    {
        return sprintf(
            '%s/' . SitemapDumper::SITEMAP_FILENAME_TEMPLATE . '.gz',
            $this->getDefaultWebsiteId(),
            self::PROVIDER,
            1
        );
    }
}
