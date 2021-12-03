<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Calculates urls based on the used Slugs.
 */
class UrlCacheMassJobProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const BATCH_SIZE = 1000;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlCacheInterface
     */
    private $cache;

    /**
     * @var RoutingInformationProvider
     */
    private $routingInformationProvider;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var int
     */
    private $batchSize = self::BATCH_SIZE;

    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ManagerRegistry $registry,
        LoggerInterface $logger,
        UrlCacheInterface $cache
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function setRoutingInformationProvider(RoutingInformationProvider $routingInformationProvider)
    {
        $this->routingInformationProvider = $routingInformationProvider;
    }

    public function setMessageFactory(MessageFactoryInterface $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    /**
     * @deprecated Not used anymore
     * @param int $batchSize
     */
    public function setBatchSize($batchSize)
    {
        $batchSize = (int)$batchSize;
        if ($batchSize < 1) {
            $batchSize = self::BATCH_SIZE;
        }

        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        foreach ($this->routingInformationProvider->getEntityClasses() as $entityClass) {
            $this->producer->send(
                Topics::PROCESS_CALCULATE_URL_CACHE_JOB,
                $this->messageFactory->createMassMessage($entityClass, [], false)
            );
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_URL_CACHE_MASS];
    }
}
