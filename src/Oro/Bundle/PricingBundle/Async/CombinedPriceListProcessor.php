<?php

namespace Oro\Bundle\PricingBundle\Async;

use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListAssociationsProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Updates combined price lists in case of changes in structure of original price lists.
 */
class CombinedPriceListProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private CombinedPriceListAssociationsProvider $cplAssociationsProvider;
    private JobRunner $jobRunner;
    private MessageProducerInterface $producer;
    private DependentJobService $dependentJob;

    public function __construct(
        CombinedPriceListAssociationsProvider $combinedPriceListByEntityProvider,
        MessageProducerInterface $producer,
        JobRunner $jobRunner,
        DependentJobService $dependentJob
    ) {
        $this->cplAssociationsProvider = $combinedPriceListByEntityProvider;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->dependentJob = $dependentJob;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [RebuildCombinedPriceListsTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            if (!$this->handlePriceListRelationTrigger($message)) {
                return self::REJECT;
            }
        } catch (InvalidArgumentException $e) {
            $this->logger?->warning(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        } catch (\Exception $e) {
            $this->logger?->error(
                'Unexpected exception occurred during Combined Price Lists build.',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    private function handlePriceListRelationTrigger(MessageInterface $message): bool
    {
        $body = $message->getBody();
        $associations = $this->getAssociations($body);

        $jobName = RebuildCombinedPriceListsTopic::getName() . ':' . md5(json_encode($body));
        $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobName,
            function (JobRunner $jobRunner, Job $job) use ($associations) {
                $this->schedulePostCplJobs($job);

                foreach ($associations as $identifier => $associationData) {
                    $jobRunner->createDelayed(
                        sprintf('%s:cpl:%s', $job->getName(), $identifier),
                        function (JobRunner $jobRunner, Job $child) use ($associationData) {
                            $this->producer->send(
                                CombineSingleCombinedPriceListPricesTopic::getName(),
                                [
                                    'collection' => $associationData['collection'],
                                    'assign_to' => $associationData['assign_to'],
                                    'jobId' => $child->getId()
                                ]
                            );
                        }
                    );
                }

                return true;
            }
        );

        return true;
    }

    private function schedulePostCplJobs(Job $job): void
    {
        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            RunCombinedPriceListPostProcessingStepsTopic::getName(),
            ['relatedJobId' => $job->getRootJob()->getId()]
        );
        $this->dependentJob->saveDependentJob($context);
    }

    private function getAssociations(array $body): array
    {
        $targetEntity = null;
        if ($body['website']) {
            $targetEntity = $body['customer'] ?: $body['customerGroup'];
        }

        return $this->cplAssociationsProvider->getCombinedPriceListsWithAssociations(
            $body['force'] ?? false,
            $body['website'],
            $targetEntity
        );
    }
}
