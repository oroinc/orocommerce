<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class SlugUrlCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->messageProducer->send(Topics::CALCULATE_URL_CACHE_MASS, '');
    }
}
