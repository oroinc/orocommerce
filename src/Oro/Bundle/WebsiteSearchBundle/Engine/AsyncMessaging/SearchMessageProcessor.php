<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Client\Config as MessageQueConfig;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\PropertyAccess\PropertyAccessor;

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
     * @param IndexerInterface $indexer
     * @param JobRunner        $jobRunner
     */
    public function __construct(IndexerInterface $indexer, JobRunner $jobRunner)
    {
        $this->indexer   = $indexer;
        $this->jobRunner = $jobRunner;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function hasEnoughDataToBuildJobName($data)
    {
        return !empty($data['class']) || !empty($data['context']);
    }

    /**
     * @param $data
     * @param $key
     * @return string
     */
    private function getSerializedValueIfKeyExists($data, $key)
    {
        $accessor = new PropertyAccessor(false, true);
        $value    = $accessor->getValue($data, $key);

        if (null !== $value) {
            return serialize($value);
        }
        return 'null';
    }

    /**
     * @param array $data
     * @return null|string
     */
    private function buildJobNameForMessage($data)
    {
        if ($this->hasEnoughDataToBuildJobName($data)) {
            return
                'website_search_reindex|' .
                md5(
                    $this->getSerializedValueIfKeyExists($data, 'class') .
                    $this->getSerializedValueIfKeyExists($data, 'context.' . AbstractIndexer::CONTEXT_WEBSITE_IDS) .
                    $this->getSerializedValueIfKeyExists($data, 'context.' . AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY)
                );
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        $result = static::REJECT;

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
                $ownerId = $message->getMessageId();
                $jobName = $this->buildJobNameForMessage($data);
                $closure = function () use ($data) {
                    return $this->indexer->reindex($data['class'], $data['context']);
                };
                if (null !== $jobName) {
                    $response = $this->jobRunner->runUnique($ownerId, $jobName, $closure);
                } else {
                    $response = $closure();
                }

                $result = $response ? static::ACK : static::REJECT;
                break;

            case AsyncIndexer::TOPIC_RESET_INDEX:
                $this->indexer->resetIndex($data['class'], $data['context']);

                $result = static::ACK;
                break;
        }

        return $result;
    }
}
