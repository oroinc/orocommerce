<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Async;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapIndexProcessor;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapIndexTopic;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class GenerateSitemapIndexProcessorTest extends WebTestCase
{
    use JobsAwareTestTrait;
    use DefaultWebsiteIdTestTrait;

    private const PROVIDER = 'page';

    private const EXAMPLE_SITEMAP = '<?xml version="1.0" encoding="UTF-8"?>
        <urlset>
            <url>
                <loc>test</loc>
                <lastmod>2018-06-04</lastmod>
            </url>
        </urlset>';

    private FileManager $tmpFileManager;

    private FileManager $publicFileManager;

    private GenerateSitemapIndexProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();

        $this->tmpFileManager = self::getContainer()->get('oro_seo.file_manager.tmp_data');
        $this->publicFileManager = self::getContainer()->get('oro_seo.file_manager');

        $this->processor = self::getContainer()->get('oro_seo.async.generate_sitemap_index');

        $this->prepareFiles();
    }

    protected function tearDown(): void
    {
        $this->clearSitemapStorage();
    }

    public function testProcess(): void
    {
        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setBody(
            [
                GenerateSitemapIndexTopic::JOB_ID => $this->createDelayedJob()->getId(),
                GenerateSitemapIndexTopic::VERSION => time(),
                GenerateSitemapIndexTopic::WEBSITE_IDS => [$this->getDefaultWebsiteId()],
            ]
        );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertSame(MessageProcessorInterface::ACK, $result);
        self::assertTrue($this->publicFileManager->hasFile($this->getFileName()));
    }

    public function testProcessReject(): void
    {
        $websiteId = 123456789;

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setBody(
            [
                GenerateSitemapIndexTopic::JOB_ID => $this->createDelayedJob()->getId(),
                GenerateSitemapIndexTopic::VERSION => time(),
                GenerateSitemapIndexTopic::WEBSITE_IDS => [$websiteId],
            ]
        );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertSame(MessageProcessorInterface::REJECT, $result);
        self::assertFalse($this->publicFileManager->hasFile($this->getFileName()));
    }

    private function prepareFiles(): void
    {
        $this->clearSitemapStorage();
        $this->tmpFileManager->writeToStorage(
            self::EXAMPLE_SITEMAP,
            $this->getFileName()
        );
    }

    private function clearSitemapStorage(): void
    {
        $this->publicFileManager->deleteAllFiles();
        $this->tmpFileManager->deleteAllFiles();
    }

    private function getFileName(): string
    {
        return sprintf(
            '%s/' . SitemapDumper::SITEMAP_FILENAME_TEMPLATE,
            $this->getDefaultWebsiteId(),
            self::PROVIDER,
            1
        );
    }
}
