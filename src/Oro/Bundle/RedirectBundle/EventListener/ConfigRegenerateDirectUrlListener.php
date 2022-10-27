<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\RedirectBundle\Async\Topic\RegenerateDirectUrlForEntityTypeTopic;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Form\Storage\RedirectStorage;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Run Direct URLs regeneration for given entityClass when configParameter is changed.
 */
class ConfigRegenerateDirectUrlListener
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var string
     */
    private $configParameter;

    /**
     * @var RedirectStorage
     */
    private $redirectStorage;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @param ConfigManager $configManager
     * @param MessageProducerInterface $messageProducer
     * @param RedirectStorage $redirectStorage
     * @param MessageFactoryInterface $messageFactory
     * @param string $configParameter
     * @param string $entityClass
     */
    public function __construct(
        ConfigManager $configManager,
        MessageProducerInterface $messageProducer,
        RedirectStorage $redirectStorage,
        MessageFactoryInterface $messageFactory,
        $configParameter,
        $entityClass
    ) {
        $this->configManager = $configManager;
        $this->messageProducer = $messageProducer;
        $this->redirectStorage = $redirectStorage;
        $this->messageFactory = $messageFactory;
        $this->configParameter = $configParameter;
        $this->entityClass = $entityClass;
    }

    public function onUpdateAfter(ConfigUpdateEvent $event)
    {
        if ($event->isChanged($this->configParameter)) {
            $prefixWithRedirect = $this->redirectStorage->getPrefixByKey($this->configParameter);
            if ($prefixWithRedirect) {
                $createRedirect = $prefixWithRedirect->getCreateRedirect();
            } else {
                $createRedirect = $this->getUseDefaultCreateRedirect();
            }

            $message = $this->messageFactory->createMassMessage($this->entityClass, [], $createRedirect);
            $this->messageProducer->send(RegenerateDirectUrlForEntityTypeTopic::getName(), $message);
        }
    }

    /**
     * @return bool
     */
    private function getUseDefaultCreateRedirect()
    {
        return $this->configManager->get('oro_redirect.redirect_generation_strategy') !== Configuration::STRATEGY_NEVER;
    }
}
