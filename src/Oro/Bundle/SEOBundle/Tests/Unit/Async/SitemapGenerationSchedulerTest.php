<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;
use Oro\Bundle\SEOBundle\Topic\GenerateSitemapTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SitemapGenerationSchedulerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageProducer;

    /**
     * @var SitemapGenerationScheduler
     */
    protected $sitemapGenerationScheduler;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->sitemapGenerationScheduler = new SitemapGenerationScheduler($this->messageProducer);
    }

    public function testScheduleSend()
    {
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(self::equalTo(GenerateSitemapTopic::getName()));

        $this->sitemapGenerationScheduler->scheduleSend();
    }
}
