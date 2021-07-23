<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Run Direct URLs regeneration when Direct URLs are enabled and removal when disabled
 */
class ConfigEnableDirectUrlListener
{
    const ORO_REDIRECT_ENABLE_DIRECT_URL = 'oro_redirect.enable_direct_url';

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var RoutingInformationProvider
     */
    private $provider;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    public function __construct(
        MessageProducerInterface $messageProducer,
        RoutingInformationProvider $provider,
        MessageFactoryInterface $messageFactory
    ) {
        $this->messageProducer = $messageProducer;
        $this->provider = $provider;
        $this->messageFactory = $messageFactory;
    }

    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(self::ORO_REDIRECT_ENABLE_DIRECT_URL)) {
            if ($event->getNewValue(self::ORO_REDIRECT_ENABLE_DIRECT_URL)) {
                foreach ($this->provider->getEntityClasses() as $entityClass) {
                    $message = $this->messageFactory->createMassMessage($entityClass, [], false);
                    $this->messageProducer->send(Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE, $message);
                }
            } else {
                foreach ($this->provider->getEntityClasses() as $entityClass) {
                    $this->messageProducer->send(
                        Topics::REMOVE_DIRECT_URL_FOR_ENTITY_TYPE,
                        JSON::encode($entityClass)
                    );
                }
            }
        }
    }
}
