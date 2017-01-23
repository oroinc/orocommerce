<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Run Direct URLs regeneration for given entityClass when configParameter is changed.
 */
class ConfigRegenerateDirectUrlListener
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var string
     */
    private $configParameter;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @param MessageProducerInterface $messageProducer
     * @param string $configParameter
     * @param string $entityClass
     */
    public function __construct(MessageProducerInterface $messageProducer, $configParameter, $entityClass)
    {
        $this->messageProducer = $messageProducer;
        $this->configParameter = $configParameter;
        $this->entityClass = $entityClass;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        if ($event->isChanged($this->configParameter)) {
            $this->messageProducer->send(
                Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE,
                JSON::encode($this->entityClass)
            );
        }
    }
}
