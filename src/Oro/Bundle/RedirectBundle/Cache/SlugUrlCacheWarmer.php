<?php

namespace Oro\Bundle\RedirectBundle\Cache;

use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheMassTopic;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Schedules Slug Url cache warming.
 */
class SlugUrlCacheWarmer implements CacheWarmerInterface
{
    private MessageProducerInterface $messageProducer;
    private RoutingInformationProvider $routingInformationProvider;
    private MessageFactoryInterface $messageFactory;

    public function __construct(
        MessageProducerInterface $messageProducer,
        RoutingInformationProvider $routingInformationProvider,
        MessageFactoryInterface $messageFactory
    ) {
        $this->messageProducer = $messageProducer;
        $this->routingInformationProvider = $routingInformationProvider;
        $this->messageFactory = $messageFactory;
    }

    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        foreach ($this->routingInformationProvider->getEntityClasses() as $entityClass) {
            $this->messageProducer->send(
                CalculateSlugCacheMassTopic::getName(),
                $this->messageFactory->createMassMessage($entityClass, [], false)
            );
        }
    }
}
