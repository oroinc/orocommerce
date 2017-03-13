<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class ContentNodeTreeCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @param MessageProducerInterface $messageProducer
     */
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
        $this->messageProducer->send(Topics::CALCULATE_WEB_CATALOG_CACHE, '');
    }
}
