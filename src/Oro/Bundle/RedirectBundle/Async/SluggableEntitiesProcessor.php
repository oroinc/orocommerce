<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Root job for mass processing of Sluggable entities.
 * Splits all entities on batches and schedules $itemProcessingTopicName MQ topic for each batch.
 * Used as root job/batch splitter for Direct URLs processing and Url Caches processing
 */
class SluggableEntitiesProcessor implements MessageProcessorInterface
{
    private const BATCH_SIZE = 1000;

    private ManagerRegistry $doctrine;
    private JobRunner $jobRunner;
    private MessageProducerInterface $producer;
    private LoggerInterface $logger;
    private MessageFactoryInterface $messageFactory;

    private int $batchSize = self::BATCH_SIZE;
    private string $itemProcessingTopicName;

    public function __construct(
        ManagerRegistry $doctrine,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        MessageFactoryInterface $messageFactory,
        string $itemProcessingTopicName
    ) {
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->messageFactory = $messageFactory;
        $this->itemProcessingTopicName = $itemProcessingTopicName;
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize > 0 ? $batchSize : self::BATCH_SIZE;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $topicName = $message->getProperty(MessageQueueConfig::PARAMETER_TOPIC_NAME);
        try {
            $messageData = JSON::decode($message->getBody());
            $entityClass = $this->messageFactory->getEntityClassFromMessage($messageData);
            $createRedirect = $this->messageFactory->getCreateRedirectFromMessage($messageData);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'Queue Message is invalid',
                [
                    'topic' => $topicName,
                    'exception' => $e
                ]
            );

            return self::REJECT;
        }

        /** @var EntityManager $em */
        if (!$em = $this->doctrine->getManagerForClass($entityClass)) {
            $this->logger->error(
                sprintf('Entity manager is not defined for class: "%s"', $entityClass)
            );

            return self::REJECT;
        }

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            sprintf('%s:%s', $topicName, $entityClass),
            function (JobRunner $jobRunner) use ($em, $entityClass, $createRedirect, $topicName) {
                $identifierFieldName = $em->getClassMetadata($entityClass)->getSingleIdentifierFieldName();
                $repository = $em->getRepository($entityClass);
                $batches = $this->getNumberOfBatches($repository);

                for ($i = 0; $i < $batches; $i++) {
                    $message = $this->messageFactory->createMassMessage(
                        $entityClass,
                        $this->getEntityIds($repository, $identifierFieldName, $i),
                        $createRedirect
                    );

                    $jobRunner->createDelayed(
                        sprintf('%s:%s:%s', $topicName, $entityClass, $i),
                        function (JobRunner $jobRunner, Job $child) use ($message) {
                            $message['jobId'] = $child->getId();

                            $this->producer->send($this->itemProcessingTopicName, $message);
                        }
                    );
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    protected function getEntityIds(EntityRepository $repository, string $identifierFieldName, int $page): array
    {
        $ids = $repository->createQueryBuilder('ids')
            ->select('ids.' . $identifierFieldName)
            ->setFirstResult($page * $this->batchSize)
            ->setMaxResults($this->batchSize)
            ->orderBy('ids.' . $identifierFieldName, 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map('current', $ids);
    }

    private function getNumberOfBatches(EntityRepository $repository): int
    {
        $entityCount = $repository->createQueryBuilder('entity')
            ->select('COUNT(entity)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)ceil($entityCount / $this->batchSize);
    }
}
