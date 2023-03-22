<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SEOBundle\Async\GenerateSitemapProcessor;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapByWebsiteAndTypeTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

class GenerateSitemapProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use JobsAwareTestTrait;

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
        $sentMessage = self::sendMessage(
            GenerateSitemapByWebsiteAndTypeTopic::getName(),
            []
        );

        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_seo.async.generate_sitemap_by_website_and_type', $sentMessage);
    }
}
