<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging;

use Monolog\Logger;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchDeleteTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexGranulizedTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchResetIndexTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchSaveTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\WebsiteSearchDeleteProcessor;
use Oro\Bundle\WebsiteSearchBundle\Async\WebsiteSearchReindexGranulizedProcessor;
use Oro\Bundle\WebsiteSearchBundle\Async\WebsiteSearchReindexProcessor;
use Oro\Bundle\WebsiteSearchBundle\Async\WebsiteSearchResetIndexProcessor;
use Oro\Bundle\WebsiteSearchBundle\Async\WebsiteSearchSaveProcessor;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\SearchProcessingEngineExceptionEvent;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRuntimeException;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

/**
 * Performs actual indexation operations requested via Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer
 *
 * @deprecated Will be removed in 5.1 Use the dedicated processors instead.
 */
class SearchMessageProcessor implements MessageProcessorInterface
{
    protected const TOPIC_REINDEX_GRANULIZED = 'oro.website.search.indexer.reindex_granulized';

    /**
     * @var IndexerInterface $indexer
     */
    private $indexer;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var IndexerInputValidator
     */
    private $inputValidator;

    /**
     * @var ReindexMessageGranularizer
     */
    private $reindexMessageGranularizer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    private ?WebsiteSearchSaveProcessor $saveProcessor = null;

    private ?WebsiteSearchDeleteProcessor $deleteProcessor = null;

    private ?WebsiteSearchResetIndexProcessor $resetIndexProcessor = null;

    private ?WebsiteSearchReindexProcessor $reindexProcessor = null;

    private ?WebsiteSearchReindexGranulizedProcessor $reindexGranulizedProcessor = null;

    public function __construct(
        IndexerInterface $indexer,
        MessageProducerInterface $messageProducer,
        IndexerInputValidator $indexerInputValidator,
        ReindexMessageGranularizer $reindexMessageGranularizer,
        JobRunner $jobRunner,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->indexer = $indexer;
        $this->messageProducer = $messageProducer;
        $this->inputValidator = $indexerInputValidator;
        $this->reindexMessageGranularizer = $reindexMessageGranularizer;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setSaveProcessor(?WebsiteSearchSaveProcessor $saveProcessor): void
    {
        $this->saveProcessor = $saveProcessor;
    }

    public function setDeleteProcessor(?WebsiteSearchDeleteProcessor $deleteProcessor): void
    {
        $this->deleteProcessor = $deleteProcessor;
    }

    public function setResetIndexProcessor(?WebsiteSearchResetIndexProcessor $resetIndexProcessor): void
    {
        $this->resetIndexProcessor = $resetIndexProcessor;
    }

    public function setReindexProcessor(?WebsiteSearchReindexProcessor $reindexProcessor): void
    {
        $this->reindexProcessor = $reindexProcessor;
    }

    public function setReindexGranulizedProcessor(
        ?WebsiteSearchReindexGranulizedProcessor $reindexGranulizedProcessor
    ): void {
        $this->reindexGranulizedProcessor = $reindexGranulizedProcessor;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        // Tries to pass the execution to the dedicated message processors.
        $topicName = $message->getProperty(MessageQueueConfig::PARAMETER_TOPIC_NAME);
        if ($topicName === WebsiteSearchSaveTopic::getName() && $this->saveProcessor) {
            return $this->saveProcessor->process($message, $session);
        }

        if ($topicName === WebsiteSearchDeleteTopic::getName() && $this->deleteProcessor) {
            return $this->deleteProcessor->process($message, $session);
        }

        if ($topicName === WebsiteSearchResetIndexTopic::getName() && $this->resetIndexProcessor) {
            return $this->resetIndexProcessor->process($message, $session);
        }

        if ($topicName === WebsiteSearchReindexTopic::getName() && $this->reindexProcessor) {
            return $this->reindexProcessor->process($message, $session);
        }

        if ($topicName === WebsiteSearchReindexGranulizedTopic::getName() && $this->reindexGranulizedProcessor) {
            return $this->reindexGranulizedProcessor->process($message, $session);
        }

        // Goes further with legacy implementation.
        try {
            $result = $this->doProcess($message);
        } catch (JobRuntimeException $exception) {
            // Child job that is interrupted by an exception is always marked for redelivery, so the message should
            // also be requeued.
            $result = self::REQUEUE;
            $this->logException(Logger::WARNING, $exception);
        } catch (\Throwable $exception) {
            $result = $this->dispatchExceptionEvent($exception) ?? self::REJECT;
            $this->logException($result === self::REQUEUE ? Logger::WARNING : Logger::ERROR, $exception);
        }

        return $result;
    }

    private function doProcess(MessageInterface $message): string
    {
        $data = JSON::decode($message->getBody());
        $topicName = $message->getProperty(MessageQueueConfig::PARAMETER_TOPIC_NAME);
        if (!empty($data['jobId'])) {
            $processResult = $this->jobRunner->runDelayed($data['jobId'], function () use ($topicName, $data) {
                unset($data['jobId']);
                return $this->tryProcessMessage($topicName, $data);
            });
        } else {
            $processResult = $this->tryProcessMessage($topicName, $data);
        }

        return $processResult === true ? self::ACK : self::REJECT;
    }

    private function tryProcessMessage(string $topicName, array $data): bool
    {
        try {
            $this->processMessage($topicName, $data);
            $result = true;
        } catch (InvalidArgumentException|\UnexpectedValueException $exception) {
            $result = false;
            $this->logException(Logger::ERROR, $exception);
        }

        return $result;
    }

    /**
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
     */
    private function processMessage(string $topicName, array $data): void
    {
        switch ($topicName) {
            case AsyncIndexer::TOPIC_SAVE:
                $parameters = $this->inputValidator->validateEntityAndContext($data);
                $this->indexer->save($parameters['entity'], $parameters['context']);

                break;
            case AsyncIndexer::TOPIC_DELETE:
                $parameters = $this->inputValidator->validateEntityAndContext($data);
                $this->indexer->delete($parameters['entity'], $parameters['context']);

                break;
            case AsyncIndexer::TOPIC_REINDEX:
                $parameters = $this->inputValidator->validateClassAndContext($data);
                $this->dispatchReindexEvent($parameters);
                $this->processReindex($parameters);

                break;
            case self::TOPIC_REINDEX_GRANULIZED:
                $parameters = $this->inputValidator->validateClassAndContext($data);
                $this->processReindex($parameters);

                break;
            case AsyncIndexer::TOPIC_RESET_INDEX:
                $parameters = $this->inputValidator->validateClassAndContext($data);
                $this->indexer->resetIndex($parameters['class'], $data['context']);

                break;
            default:
                throw new \UnexpectedValueException(sprintf('Topic name "%s" not supported!', $topicName));
        }
    }

    private function processReindex(array $parameters): void
    {
        if ($parameters['granulize']) {
            $websiteIdsToIndex = $parameters['context'][AbstractIndexer::CONTEXT_WEBSITE_IDS];
            $reindexMsgData = $this->reindexMessageGranularizer->process(
                $parameters['class'],
                $websiteIdsToIndex,
                $parameters['context']
            );

            // If data scope is small it can be processed without triggering new messages,
            // Batch size should be the same as in granulizer
            $batchSize = count($parameters['class']) * count($websiteIdsToIndex);
            // As granulizer returns iterable but not countable we can't count messages, buffer used instead
            // in order to process data without triggering new messages when it's smaller than batch size
            $buffer = [];
            $enableBuffer = true;
            foreach ($reindexMsgData as $msgData) {
                if ($enableBuffer) {
                    if (count($buffer) <= $batchSize) {
                        $buffer[] = $msgData;
                        continue;
                    }
                    // If this line of code were reached, there are more messages than batch size.
                    // Send buffered messages and clear the buffer as data should be processed asynchronously
                    foreach ($buffer as $bufferMsgData) {
                        $this->messageProducer->send(
                            self::TOPIC_REINDEX_GRANULIZED,
                            $bufferMsgData
                        );
                    }
                    $buffer = [];
                    $enableBuffer = false;
                }
                $this->messageProducer->send(
                    self::TOPIC_REINDEX_GRANULIZED,
                    $msgData
                );
            }

            // Process data without triggering new messages if the buffer isn't empty
            foreach ($buffer as $msgData) {
                $this->indexer->reindex(
                    $msgData['class'],
                    array_merge($msgData['context'], ['skip_pre_processing' => true])
                );
            }
        } else {
            $this->indexer->reindex(
                $parameters['class'],
                array_merge($parameters['context'], ['skip_pre_processing' => true])
            );
        }
    }

    private function logException(int $level, \Exception $exception): void
    {
        $message = 'An unexpected exception occurred during indexation';
        $this->logger->log($level, $message, ['exception' => $exception]);
    }

    private function dispatchReindexEvent(array $parameters): void
    {
        $event = new BeforeReindexEvent($parameters['class'], $parameters['context']);
        $this->eventDispatcher->dispatch($event, BeforeReindexEvent::EVENT_NAME);
    }

    private function dispatchExceptionEvent(\Exception $exception): ?string
    {
        $event = new SearchProcessingEngineExceptionEvent($exception);
        $this->eventDispatcher->dispatch($event, SearchProcessingEngineExceptionEvent::EVENT_NAME);

        return $event->getConsumptionResult();
    }
}
