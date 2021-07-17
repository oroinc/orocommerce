<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
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

    /**
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
        try {
            if ($this->cache instanceof ClearableCache) {
                $this->cache->deleteAll();
            }

            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                Topics::CALCULATE_URL_CACHE_MASS,
                function (JobRunner $jobRunner) {
                    $repository = $this->registry->getManagerForClass(Slug::class)
                        ->getRepository(Slug::class);

                    $usedRoutes = $repository->getUsedRoutes();

                    foreach ($usedRoutes as $usedRoute) {
                        $entityCount = $repository->getSlugsCountByRoute($usedRoute);
                        $batches = (int)ceil($entityCount / $this->batchSize);

                        for ($i = 0; $i < $batches; $i++) {
                            $this->scheduleRecalculationForRouteByScope($jobRunner, $repository, $usedRoute, $i);
                        }
                    }

                    return true;
                }
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'topic' => Topics::CALCULATE_URL_CACHE_MASS,
                    'exception' => $e
                ]
            );

            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param JobRunner $jobRunner
     * @param SlugRepository $repo
     * @param string $usedRoute
     * @param int $page
     */
    private function scheduleRecalculationForRouteByScope(JobRunner $jobRunner, SlugRepository $repo, $usedRoute, $page)
    {
        $entityIds = $repo->getSlugIdsByRoute($usedRoute, $page, $this->batchSize);

        $jobRunner->createDelayed(
            sprintf(
                '%s:%s:%s',
                Topics::PROCESS_CALCULATE_URL_CACHE_JOB,
                $usedRoute,
                $page
            ),
            function (JobRunner $jobRunner, Job $child) use ($usedRoute, $entityIds) {
                $this->producer->send(Topics::PROCESS_CALCULATE_URL_CACHE_JOB, [
                    'route_name' => $usedRoute,
                    'entity_ids' => $entityIds,
                    'jobId' => $child->getId(),
                ]);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_URL_CACHE_MASS];
    }
}
