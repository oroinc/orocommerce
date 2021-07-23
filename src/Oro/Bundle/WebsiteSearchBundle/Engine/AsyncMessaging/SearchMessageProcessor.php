<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\SearchProcessingEngineExceptionEvent;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

/**
 * Performs actual indexation operations requested via Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer
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

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $result = $this->doProcess($message, $session);
        } catch (InvalidArgumentException | \UnexpectedValueException $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            $result = self::REJECT;
        } catch (\Exception $exception) {
            $result = $this->dispatchExceptionEvent($exception) ?? self::REJECT;
            $this->logException($result, $exception);
        }

        return $result;
    }

    /**
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
     */
    private function doProcess(MessageInterface $message, SessionInterface $session): string
    {
        $data = JSON::decode($message->getBody());
        $topicName = $message->getProperty(MessageQueueConfig::PARAMETER_TOPIC_NAME);
        if (!empty($data['jobId'])) {
            $processResult = $this->jobRunner->runDelayed($data['jobId'], function () use ($topicName, $data) {
                unset($data['jobId']);
                return $this->processMessage($topicName, $data);
            });
        } else {
            $processResult = $this->processMessage($topicName, $data);
        }

        return $processResult ? self::ACK : self::REQUEUE;
    }

    /**
     * @throws \UnexpectedValueException
     * @throws InvalidArgumentException
     */
    private function processMessage(string $topicName, array $data): bool
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

        return true;
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

    private function logException(string $result, \Exception $exception): void
    {
        $message = 'An unexpected exception occurred during indexation';
        if (self::REQUEUE === $result) {
            $this->logger->warning($message, ['exception' => $exception]);
        } else {
            $this->logger->error($message, ['exception' => $exception]);
        }
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
