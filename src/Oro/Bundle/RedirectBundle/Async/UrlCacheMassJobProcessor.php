<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

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
     * @var SlugRepository
     */
    private $slugRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlStorageCache
     */
    private $cache;

    /**
     * @var int
     */
    private $batchSize = self::BATCH_SIZE;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param SlugRepository $slugRepository
     * @param LoggerInterface $logger
     * @param UrlStorageCache $cache
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        SlugRepository $slugRepository,
        LoggerInterface $logger,
        UrlStorageCache $cache
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->slugRepository = $slugRepository;
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
            $this->cache->deleteAll();

            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                Topics::CALCULATE_URL_CACHE_MASS,
                function (JobRunner $jobRunner) {
                    $usedRoutes = $this->slugRepository->getUsedRoutes();

                    foreach ($usedRoutes as $usedRoute) {
                        $entityCount = $this->slugRepository->getSlugsCountByRoute($usedRoute);
                        $batches = (int)ceil($entityCount / $this->batchSize);

                        for ($i = 0; $i < $batches; $i++) {
                            $this->scheduleRecalculationForRouteByScope($jobRunner, $usedRoute, $i);
                        }
                    }

                    return true;
                }
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'message' => $message->getBody(),
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
     * @param string $usedRoute
     * @param int $page
     */
    private function scheduleRecalculationForRouteByScope(JobRunner $jobRunner, $usedRoute, $page)
    {
        $entityIds = $this->slugRepository->getSlugIdsByRoute($usedRoute, $page, $this->batchSize);

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
