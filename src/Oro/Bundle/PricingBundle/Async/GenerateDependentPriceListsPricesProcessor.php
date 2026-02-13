<?php

namespace Oro\Bundle\PricingBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateSinglePriceListPricesByRulesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByVersionedPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveVersionedFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Generates prices for dependent price lists based on the Price Rules.
 */
class GenerateDependentPriceListsPricesProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface,
    FeatureToggleableInterface
{
    use LoggerAwareTrait;
    use FeatureCheckerHolderTrait;

    public function __construct(
        private ManagerRegistry $doctrine,
        private DependentPriceListProvider $dependentPriceListProvider,
        private MessageProducerInterface $messageProducer,
        private JobRunner $jobRunner,
        private DependentJobService $dependentJob
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [GenerateDependentPriceListPricesTopic::getName()];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageData = $message->getBody();
        if ($messageData === null) {
            return self::REJECT;
        }

        try {
            if ($this->messageProducer instanceof BufferedMessageProducer) {
                $this->messageProducer->disableBuffering();
            }

            $waves = $this->dependentPriceListProvider
                ->getResolvedOrderedDependencies($messageData['sourcePriceListId']);
            $processWave = $messageData['level'] + 1;
            $version = $messageData['version'];

            // If there are no dependent prices, then run only post-processing tasks
            if (!isset($waves[$processWave])) {
                $this->immediatelySendPostGenerateMessages($messageData, $waves);

                return self::ACK;
            }

            $result = $this->jobRunner->runUniqueByMessage(
                $message,
                function (JobRunner $jobRunner, Job $job) use ($waves, $messageData, $processWave, $version) {
                    if (isset($waves[$processWave + 1])) {
                        $this->scheduleNextWave($job, $messageData, $processWave);
                    } else {
                        $this->schedulePostGenerateJobs($job, $messageData, $waves);
                    }

                    $batchNumber = 0;
                    // productBatches is a generator that can be used only once
                    foreach ($messageData['productBatches'] as $productsBatch) {
                        foreach ($waves[$processWave] as $priceListId) {
                            $jobRunner->createDelayed(
                                sprintf('%s:pl:%s:batch:%d', $job->getName(), $priceListId, $batchNumber),
                                function (
                                    JobRunner $jobRunner,
                                    Job $child
                                ) use (
                                    $priceListId,
                                    $productsBatch,
                                    $version
                                ) {
                                    $this->messageProducer->send(
                                        GenerateSinglePriceListPricesByRulesTopic::getName(),
                                        [
                                            'priceListId' => $priceListId,
                                            'products' => $productsBatch,
                                            'version' => $version,
                                            'jobId' => $child->getId()
                                        ]
                                    );
                                }
                            );
                        }
                        $batchNumber++;
                    }

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (\Exception $e) {
            $this->logger?->error(
                'Unexpected exception occurred during Price Lists prices generation.',
                ['exception' => $e]
            );

            return self::REJECT;
        } finally {
            if ($this->messageProducer instanceof BufferedMessageProducer) {
                $this->messageProducer->enableBuffering();
            }
        }
    }

    private function scheduleNextWave(Job $job, array $messageData, int $wave): void
    {
        $messageData['level'] = $wave;
        $messageData['baseJobId'] ??= $job->getId();
        unset($messageData['productBatches']);

        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            GenerateDependentPriceListPricesTopic::getName(),
            $messageData
        );
        $this->dependentJob->saveDependentJob($context);
    }

    private function schedulePostGenerateJobs(Job $job, array $messageData, array $waves): void
    {
        $priceListIds = $this->doctrine
            ->getRepository(PriceList::class)
            ->getActivePriceListIdsByIds(array_merge(...$waves));

        if (!$priceListIds) {
            return;
        }

        if ($this->featureChecker->isFeatureEnabled('oro_price_lists_combined')) {
            $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
            $context->addDependentJob(
                ResolveCombinedPriceByVersionedPriceListTopic::getName(),
                [
                    'version' => $messageData['version'],
                    'priceLists' => $priceListIds
                ]
            );
            $this->dependentJob->saveDependentJob($context);
        } elseif ($this->featureChecker->isFeatureEnabled('oro_price_lists_flat')) {
            $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
            $context->addDependentJob(
                ResolveVersionedFlatPriceTopic::getName(),
                [
                    'version' => $messageData['version'],
                    'priceLists' => $priceListIds
                ]
            );
            $this->dependentJob->saveDependentJob($context);
        }
    }

    private function immediatelySendPostGenerateMessages(array $messageData, array $waves): void
    {
        $priceListIds = $this->doctrine
            ->getRepository(PriceList::class)
            ->getActivePriceListIdsByIds(array_merge(...$waves));

        if (!$priceListIds) {
            return;
        }

        if ($this->featureChecker->isFeatureEnabled('oro_price_lists_combined')) {
            $this->messageProducer->send(
                ResolveCombinedPriceByVersionedPriceListTopic::getName(),
                [
                    'version' => $messageData['version'],
                    'priceLists' => $priceListIds
                ]
            );
        } elseif ($this->featureChecker->isFeatureEnabled('oro_price_lists_flat')) {
            $this->messageProducer->send(
                ResolveVersionedFlatPriceTopic::getName(),
                [
                    'version' => $messageData['version'],
                    'priceLists' => $priceListIds
                ]
            );
        }
    }
}
