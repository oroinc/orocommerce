<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;
use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SitemapGenerationSchedulerTest extends \PHPUnit\Framework\TestCase
{
    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private SitemapGenerationScheduler $sitemapGenerationScheduler;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->sitemapGenerationScheduler = new SitemapGenerationScheduler($this->messageProducer);
    }

    public function testScheduleSend(): void
    {
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(self::equalTo(GenerateSitemapTopic::getName()));

        $this->sitemapGenerationScheduler->scheduleSend();
    }
}
