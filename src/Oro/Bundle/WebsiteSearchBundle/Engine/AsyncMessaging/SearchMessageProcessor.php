<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Component\MessageQueue\Client\Config as MessageQueConfig;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Performs actual indexation operations requested via Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer
 */
class SearchMessageProcessor implements MessageProcessorInterface
{
    /**
     * @var IndexerInterface $indexer
     */
    private $indexer;

    /**
     * @var JobRunner
     */
    private $jobRunner;

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
     * @var DatabaseExceptionHelper
     */
    private $databaseExceptionHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IndexerInterface $indexer
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $messageProducer
     * @param IndexerInputValidator $indexerInputValidator
     * @param ReindexMessageGranularizer $reindexMessageGranularizer
     */
    public function __construct(
        IndexerInterface $indexer,
        JobRunner $jobRunner,
        MessageProducerInterface $messageProducer,
        IndexerInputValidator $indexerInputValidator,
        ReindexMessageGranularizer $reindexMessageGranularizer
    ) {
        $this->indexer                    = $indexer;
        $this->jobRunner                  = $jobRunner;
        $this->messageProducer            = $messageProducer;
        $this->inputValidator             = $indexerInputValidator;
        $this->reindexMessageGranularizer = $reindexMessageGranularizer;
    }

    /**
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     */
    public function setDatabaseExceptionHelper(DatabaseExceptionHelper $databaseExceptionHelper)
    {
        $this->databaseExceptionHelper = $databaseExceptionHelper;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if (null === $this->databaseExceptionHelper) {
            throw new \InvalidArgumentException('DatabaseExceptionHelper not injected');
        }
        try {
            $result = $this->processMessage($message);
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->error(
                    'An unexpected exception occurred during indexation',
                    ['exception' => $e]
                );
            }

            $driverException = $this->databaseExceptionHelper->getDriverException($e);
            if (($driverException && $this->databaseExceptionHelper->isDeadlock($driverException))
                || $e instanceof UniqueConstraintViolationException
                || $e instanceof ForeignKeyConstraintViolationException) {
                $result = static::REQUEUE;
            } else {
                $result = static::REJECT;
            }
        }

        return $result;
    }

    /**
     * @param MessageInterface $message
     *
     * @return string
     */
    private function processMessage(MessageInterface $message)
    {
        $data = JSON::decode($message->getBody());

        $result = static::REJECT;
        // REJECT messages that are a part of job
        if (!empty($data['jobId'])) {
            return $result;
        }
        switch ($message->getProperty(MessageQueConfig::PARAMETER_TOPIC_NAME)) {
            case AsyncIndexer::TOPIC_SAVE:
                $this->indexer->save($data['entity'], $data['context']);

                $result = static::ACK;
                break;

            case AsyncIndexer::TOPIC_DELETE:
                $this->indexer->delete($data['entity'], $data['context']);

                $result = static::ACK;
                break;

            case AsyncIndexer::TOPIC_REINDEX:
                $this->processReindex($data);

                $result = static::ACK;
                break;

            case AsyncIndexer::TOPIC_RESET_INDEX:
                $this->indexer->resetIndex($data['class'], $data['context']);

                $result = static::ACK;
                break;
        }

        return $result;
    }

    /**
     * @param array $data
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    private function processReindex($data)
    {
        if (!empty($data['granulize'])) {
            list($entityClassesToIndex, $websiteIdsToIndex) =
                $this->inputValidator->validateReindexRequest($data['class'], $data['context']);

            $reindexMsgData = $this->reindexMessageGranularizer->process(
                $entityClassesToIndex,
                $websiteIdsToIndex,
                $data['context']
            );

            // If data scope is small it can be processed without triggering new messages,
            // Batch size should be the same as in granulizer
            $batchSize = count($entityClassesToIndex) * count($websiteIdsToIndex);
            // As granulizer returns iterable but not countable we can't count messages, buffer used instead
            // in order to process data without triggering new messages when it's smaller than batch size
            $buffer       = [];
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
                            AsyncIndexer::TOPIC_REINDEX,
                            $bufferMsgData
                        );
                    }
                    $buffer       = [];
                    $enableBuffer = false;
                }
                $this->messageProducer->send(
                    AsyncIndexer::TOPIC_REINDEX,
                    $msgData
                );
            }

            // Process data without triggering new messages if the buffer isn't empty
            foreach ($buffer as $msgData) {
                $this->indexer->reindex($msgData['class'], $msgData['context']);
            }
        } else {
            $this->indexer->reindex($data['class'], $data['context']);
        }
    }
}
