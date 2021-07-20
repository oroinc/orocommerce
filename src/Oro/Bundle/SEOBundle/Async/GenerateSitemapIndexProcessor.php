<?php

namespace Oro\Bundle\SEOBundle\Async;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generates sitemap indexes for all websites and write it to a temporary storage.
 */
class GenerateSitemapIndexProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const JOB_ID      = 'jobId';
    private const VERSION     = 'version';
    private const WEBSITE_IDS = 'websiteIds';

    /** @var JobRunner */
    private $jobRunner;

    /** @var DependentJobService */
    private $dependentJob;

    /** @var MessageProducerInterface */
    private $producer;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        JobRunner $jobRunner,
        DependentJobService $dependentJob,
        MessageProducerInterface $producer,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->dependentJob = $dependentJob;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SITEMAP_INDEX];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $this->resolveMessage($message);
        if (null === $body) {
            return self::REJECT;
        }

        $version = $body[self::VERSION];
        $websiteIds = $body[self::WEBSITE_IDS];
        try {
            $result = $this->jobRunner->runUnique(
                $message->getMessageId(),
                Topics::GENERATE_SITEMAP . ':index',
                function (JobRunner $jobRunner, Job $job) use ($version, $websiteIds) {
                    $this->createFinishJob($job, $version, $websiteIds);
                    $this->scheduleGeneratingSitemapIndex($jobRunner, $version, $websiteIds);

                    return true;
                }
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during generating sitemap indexes.',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return $result ? self::ACK : self::REJECT;
    }

    private function resolveMessage(MessageInterface $message): ?array
    {
        try {
            return $this->getMessageResolver()->resolve(JSON::decode($message->getBody()));
        } catch (\Throwable $e) {
            $this->logger->critical('Got invalid message.', ['exception' => $e]);
        }

        return null;
    }

    private function getMessageResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired([self::JOB_ID, self::VERSION, self::WEBSITE_IDS]);
        $resolver->setAllowedTypes(self::JOB_ID, ['int']);
        $resolver->setAllowedTypes(self::VERSION, ['int']);
        $resolver->setAllowedTypes(self::WEBSITE_IDS, ['array']);

        return $resolver;
    }

    /**
     * @param Job   $job
     * @param int   $version
     * @param int[] $websiteIds
     */
    private function createFinishJob(Job $job, int $version, array $websiteIds): void
    {
        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            Topics::GENERATE_SITEMAP_MOVE_GENERATED_FILES,
            ['version' => $version, 'websiteIds' => $websiteIds]
        );
        $this->dependentJob->saveDependentJob($context);
    }

    private function scheduleGeneratingSitemapIndex(JobRunner $jobRunner, int $version, array $websiteIds): void
    {
        foreach ($websiteIds as $websiteId) {
            $jobRunner->createDelayed(
                sprintf('%s:%s:%s', Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE, $websiteId, $version),
                function (JobRunner $jobRunner, Job $job) use ($version, $websiteId) {
                    $this->producer->send(
                        Topics::GENERATE_SITEMAP_INDEX_BY_WEBSITE,
                        ['jobId' => $job->getId(), 'version' => $version, 'websiteId' => $websiteId]
                    );

                    return true;
                }
            );
        }
    }
}
