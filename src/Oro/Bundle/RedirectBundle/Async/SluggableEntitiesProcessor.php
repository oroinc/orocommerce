<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Root job for mass processing of Sluggable entities.
 * Splits all entities on batches and schedules $itemProcessingTopicName MQ topic for each batch.
 * Used as root job/batch splitter for Direct URLs processing and Url Caches processing
 */
class SluggableEntitiesProcessor implements MessageProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const BATCH_SIZE = 1000;

    private ManagerRegistry $doctrine;

    private JobRunner $jobRunner;

    private MessageProducerInterface $producer;

    private MessageFactoryInterface $messageFactory;

    private int $batchSize = self::BATCH_SIZE;

    private string $itemProcessingTopicName;

    public function __construct(
        ManagerRegistry $doctrine,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        MessageFactoryInterface $messageFactory,
        string $itemProcessingTopicName
    ) {
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->messageFactory = $messageFactory;
        $this->itemProcessingTopicName = $itemProcessingTopicName;

        $this->logger = new NullLogger();
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
        $messageData = $message->getBody();
        $entityClass = $this->messageFactory->getEntityClassFromMessage($messageData);
        $createRedirect = $this->messageFactory->getCreateRedirectFromMessage($messageData);

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            sprintf('%s:%s', $topicName, $entityClass),
            function (JobRunner $jobRunner) use ($entityClass, $createRedirect, $topicName) {
                /** @var EntityManager $em */
                $em = $this->doctrine->getManagerForClass($entityClass);
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
