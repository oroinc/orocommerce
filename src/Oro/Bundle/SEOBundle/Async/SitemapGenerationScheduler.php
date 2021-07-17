<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SitemapGenerationScheduler
{
    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    public function scheduleSend()
    {
        $this->messageProducer->send(Topics::GENERATE_SITEMAP, '');
    }
}
