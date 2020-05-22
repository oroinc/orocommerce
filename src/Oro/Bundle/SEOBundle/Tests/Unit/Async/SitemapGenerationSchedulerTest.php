<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;
use Oro\Bundle\SEOBundle\Async\Topics;
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
        $this->messageProducer->expects(static::once())
            ->method('send')
            ->with($this->equalTo(Topics::GENERATE_SITEMAP));

        $this->sitemapGenerationScheduler->scheduleSend();
    }
}
