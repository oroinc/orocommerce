<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchDeleteTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchResetIndexTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchSaveTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Asynchronous indexer for website search engine
 * Used to redirect indexation requests to message queue
 */
class AsyncIndexer implements IndexerInterface
{
    /**
     * @var IndexerInterface
     */
    private $baseIndexer;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var IndexerInputValidator
     */
    private $inputValidator;

    public function __construct(
        IndexerInterface $baseIndexer,
        MessageProducerInterface $messageProducer,
        IndexerInputValidator $indexerInputValidator
    ) {
        $this->baseIndexer = $baseIndexer;
        $this->messageProducer = $messageProducer;
        $this->inputValidator = $indexerInputValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, array $context = [])
    {
        $this->sendAsyncIndexerMessage(
            WebsiteSearchSaveTopic::getName(),
            [
                'entity' => $this->getEntityData($entity),
                'context' => $context,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, array $context = [])
    {
        $this->sendAsyncIndexerMessage(
            WebsiteSearchDeleteTopic::getName(),
            [
                'entity' => $this->getEntityData($entity),
                'context' => $context,
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
            WebsiteSearchResetIndexTopic::getName(),
            [
                'class' => $class,
                'context' => $context,
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
        $parameters = $this->inputValidator->validateClassAndContext([
            'granulize' => true,
            'class' => $class,
            'context' => $context,
        ]);

        // granulization might take quite a lot of time, so it has to happen asynchronously inside a processor
        $this->sendAsyncIndexerMessage(WebsiteSearchReindexTopic::getName(), $parameters);
    }

    /**
     * Send a message to a queue using message producer
     */
    private function sendAsyncIndexerMessage(string $topicName, array $messageBody): void
    {
        $this->messageProducer->send($topicName, $messageBody);
    }

    /**
     * @param object|array $entity
     *
     * @return array<array{class: string, id: int}>
     */
    private function getEntityData(object|array $entity): array
    {
        $entity = is_array($entity) ? $entity : [$entity];

        return array_map(fn (object $entity) => $this->getEntityScalarRepresentation($entity), $entity);
    }

    /**
     * Parse entity and get the Id and class name from it, to send in the que message.
     *
     * @param object $entity
     *
     * @return array{class: string, id: int}
     *
     * @throws \RuntimeException
     */
    private function getEntityScalarRepresentation(object $entity): array
    {
        if (method_exists($entity, 'getId')) {
            return [
                'class' => get_class($entity),
                'id' => $entity->getId(),
            ];
        }

        throw new \RuntimeException('Id can not be found in the given entity.');
    }
}
