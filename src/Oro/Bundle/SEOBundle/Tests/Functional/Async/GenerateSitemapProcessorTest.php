<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapProcessor;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapByWebsiteAndTypeTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class GenerateSitemapProcessorTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    private GenerateSitemapProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();

        $this->processor = self::getContainer()
            ->get('oro_seo.async.generate_sitemap_processor');
    }

    protected function tearDown(): void
    {
        self::clearMessageCollector();
    }

    public function testProcess(): void
    {
        self::assertMessagesEmpty(GenerateSitemapByWebsiteAndTypeTopic::getName());

        $message = new Message();
        $message->setMessageId(UUIDGenerator::v4());
        $message->setBody([]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertSame(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(GenerateSitemapByWebsiteAndTypeTopic::getName());
    }
}
