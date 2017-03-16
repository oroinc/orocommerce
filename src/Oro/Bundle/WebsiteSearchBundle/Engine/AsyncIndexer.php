<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AsyncIndexer implements IndexerInterface
{
    const TOPIC_SAVE = 'oro.website.search.indexer.save';
    const TOPIC_DELETE = 'oro.website.search.indexer.delete';
    const TOPIC_RESET_INDEX = 'oro.website.search.indexer.reset_index';
    const TOPIC_REINDEX = 'oro.website.search.indexer.reindex';

    /**
     * @var IndexerInterface
     */
    private $baseIndexer;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var ReindexMessageGranularizer
     */
    private $reindexMessageGranularizer;

    /**
     * @var IndexerInputValidator
     */
    private $inputValidator;

    /**
     * @param IndexerInterface $baseIndexer
     * @param MessageProducerInterface $messageProducer
     * @param IndexerInputValidator $indexerInputValidator
     * @param ReindexMessageGranularizer $reindexMessageGranularizer
     */
    public function __construct(
        IndexerInterface $baseIndexer,
        MessageProducerInterface $messageProducer,
        IndexerInputValidator $indexerInputValidator,
        ReindexMessageGranularizer $reindexMessageGranularizer
    ) {
        $this->baseIndexer                = $baseIndexer;
        $this->messageProducer            = $messageProducer;
        $this->inputValidator             = $indexerInputValidator;
        $this->reindexMessageGranularizer = $reindexMessageGranularizer;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, array $context = [])
    {
        $this->sendAsyncIndexerMessage(
            self::TOPIC_SAVE,
            [
                'entity' => $this->getEntityData($entity),
                'context' => $context
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, array $context = [])
    {
        $this->sendAsyncIndexerMessage(
            self::TOPIC_DELETE,
            [
                'entity' => $this->getEntityData($entity),
                'context' => $context
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param array $context Not used here, only to comply with the interface
     */
    public function getClassesForReindex($class = null, array $context = [])
    {
        return $this->baseIndexer->getClassesForReindex($class, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        $this->sendAsyncIndexerMessage(
            self::TOPIC_RESET_INDEX,
            [
                'class' => $class,
                'context' => $context
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param array $context
     * $context = [
     *     'entityIds' int[] Array of entities ids to reindex
     *     'websiteIds' int[] Array of websites ids to reindex
     * ]
     */
    public function reindex($class = null, array $context = [])
    {
        list($entityClassesToIndex, $websiteIdsToIndex) =
            $this->inputValidator->validateReindexRequest(
                $class,
                $context
            );

        $reindexMsgData = $this->reindexMessageGranularizer->process(
            $entityClassesToIndex,
            $websiteIdsToIndex,
            $context
        );

        foreach ($reindexMsgData as $msgData) {
            $this->sendAsyncIndexerMessage(
                self::TOPIC_REINDEX,
                $msgData
            );
        }
    }

    /**
     * Send a message to a que using message producer
     *
     * @param $topic
     * @param array $data
     */
    private function sendAsyncIndexerMessage($topic, array $data)
    {
        $this->messageProducer->send(
            $topic,
            $data
        );
    }

    /**
     * @param object|object[] $entity
     * @return array
     */
    private function getEntityData($entity)
    {
        if (is_array($entity)) {
            $result = [];

            foreach ($entity as $entityEntry) {
                $result[] = $this->getEntityScalarRepresentation($entityEntry);
            }

            return $result;
        }

        return $this->getEntityScalarRepresentation($entity);
    }

    /**
     * Parse entity and get the Id and class name from it, to send in the que message.
     *
     * @param object $entity
     * @return array
     * @throws \RuntimeException
     */
    private function getEntityScalarRepresentation($entity)
    {
        if (is_object($entity) && method_exists($entity, 'getId')) {
            return [
                'class' => get_class($entity),
                'id' => $entity->getId()
            ];
        }

        throw new \RuntimeException('Id can not be found in the given entity.');
    }
}
