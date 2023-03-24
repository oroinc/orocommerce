<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Async\Topic\CombineSingleCombinedPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListAssociationsProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\PhpUtils\ArrayUtil;
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
    private ManagerRegistry $doctrine;

    public function __construct(
        CombinedPriceListAssociationsProvider $combinedPriceListByEntityProvider,
        MessageProducerInterface $producer,
        JobRunner $jobRunner,
        DependentJobService $dependentJob,
        ManagerRegistry $doctrine
    ) {
        $this->cplAssociationsProvider = $combinedPriceListByEntityProvider;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->dependentJob = $dependentJob;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [MassRebuildCombinedPriceListsTopic::getName()];
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

        $this->jobRunner->runUniqueByMessage(
            $message,
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
                                    'jobId' => $child->getId(),
                                    'version' => time()
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
        $associations = [];
        foreach ($body['assignments'] as $item) {
            $this->mergeAssociations($associations, $this->getCombinedPriceListAssociations($item));
        }

        return $associations;
    }

    private function mergeAssociations(array &$associations, array $association): void
    {
        foreach ($association as $identifier => $data) {
            if (!array_key_exists($identifier, $associations)) {
                $associations[$identifier] = $data;
            } else {
                $associations[$identifier]['assign_to'] = ArrayUtil::arrayMergeRecursiveDistinct(
                    $associations[$identifier]['assign_to'] ?? [],
                    $data['assign_to']
                );
            }
        }
    }

    private function getCombinedPriceListAssociations(array $item): array
    {
        $isForce = $item['force'];
        $website = $this->getReference($item, 'website', Website::class);
        $targetEntity =
            $this->getReference($item, 'customer', Customer::class) ?:
            $this->getReference($item, 'customerGroup', CustomerGroup::class);

        return $this->cplAssociationsProvider->getCombinedPriceListsWithAssociations($isForce, $website, $targetEntity);
    }

    private function getReference(array $item, string $key, string $className): ?object
    {
        return isset($item[$key])
            ? $this->doctrine->getManagerForClass($className)->getReference($className, $item[$key])
            : null;
    }
}
