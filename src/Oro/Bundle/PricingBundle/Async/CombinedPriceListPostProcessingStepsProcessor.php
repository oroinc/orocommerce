<?php

namespace Oro\Bundle\PricingBundle\Async;

use Oro\Bundle\PricingBundle\Async\Topic\RunCombinedPriceListPostProcessingStepsTopic;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Perform action required after combined price lists build.
 *
 * - execute CombinedPriceListGarbageCollector::cleanCombinedPriceLists
 * - gather re-indexation requests produced by GC
 * - execute ReindexRequestItemProductsByRelatedJobIdTopic
 */
class CombinedPriceListPostProcessingStepsProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private CombinedPriceListGarbageCollector $garbageCollector;
    private CombinedPriceListTriggerHandler $triggerHandler;
    private MessageProducerInterface $producer;

    public function __construct(
        CombinedPriceListGarbageCollector $garbageCollector,
        CombinedPriceListTriggerHandler $triggerHandler,
        MessageProducerInterface $producer
    ) {
        $this->garbageCollector = $garbageCollector;
        $this->triggerHandler = $triggerHandler;
        $this->producer = $producer;
    }

    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageData = $message->getBody();
        $jobId = $messageData['relatedJobId'];
        $cpls = $messageData['cpls'];

        $this->executeGc($jobId, $cpls);
        $this->executeScheduledProductsIndexation($jobId);

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [RunCombinedPriceListPostProcessingStepsTopic::getName()];
    }

    private function executeGc(int $jobId, array $cpls = []): void
    {
        try {
            $this->triggerHandler->startCollect($jobId);
            $this->garbageCollector->cleanCombinedPriceLists($cpls);
            $this->triggerHandler->commit();
        } catch (\Exception $e) {
            $this->triggerHandler->rollback();
            $this->logger?->error(
                'Unexpected exception occurred during Combined Price Lists garbage collection.',
                [
                    'topic' => RunCombinedPriceListPostProcessingStepsTopic::getName(),
                    'exception' => $e
                ]
            );
        }
    }

    private function executeScheduledProductsIndexation(int $jobId): void
    {
        $this->producer->send(
            ReindexRequestItemProductsByRelatedJobIdTopic::getName(),
            [
                'relatedJobId' => $jobId,
                'indexationFieldsGroups' => ['pricing']
            ]
        );
    }
}
