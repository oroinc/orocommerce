<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Console scheduler to generate sitemap
 */
class SitemapGenerationScheduler
{
    protected MessageProducerInterface $messageProducer;

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    public function scheduleSend()
    {
        $this->messageProducer->send(GenerateSitemapTopic::getName(), []);
    }
}
