<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerException;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Client\Config as MessageQueConfig;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class SearchMessageProcessor implements MessageProcessorInterface
{
    /**
     * @var IndexerInterface $indexer
     */
    private $indexer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IndexerInterface $indexer
     * @param LoggerInterface  $logger
     */
    public function __construct(IndexerInterface $indexer, LoggerInterface $logger)
    {
        $this->indexer = $indexer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $topic = $message->getProperty(MessageQueConfig::PARAMETER_TOPIC_NAME);
        $data = JSON::decode($message->getBody());

        try {
            $result = $this->executeIndexActionByTopic($topic, $data);
        } catch (IndexerException $e) {
            $result = static::REQUEUE;

            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    /**
     * @param string $topic
     * @param mixed  $data
     *
     * @return string
     */
    protected function executeIndexActionByTopic(string $topic, $data): string
    {
        switch ($topic) {
            case AsyncIndexer::TOPIC_SAVE:
                $this->indexer->save($data['entity'], $data['context']);

                $result = static::ACK;
                break;

            case AsyncIndexer::TOPIC_DELETE:
                $this->indexer->delete($data['entity'], $data['context']);

                $result = static::ACK;
                break;

            case AsyncIndexer::TOPIC_REINDEX:
                $this->indexer->reindex($data['class'], $data['context']);

                $result = static::ACK;
                break;

            case AsyncIndexer::TOPIC_RESET_INDEX:
                $this->indexer->resetIndex($data['class'], $data['context']);

                $result = static::ACK;
                break;

            default:
                $result = static::REJECT;
        }

        return $result;
    }
}
