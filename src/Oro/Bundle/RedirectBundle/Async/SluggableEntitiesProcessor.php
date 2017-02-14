<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class SluggableEntitiesProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const BATCH_SIZE = 1000;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var int
     */
    private $batchSize = self::BATCH_SIZE;

    /**
     * @param ManagerRegistry $doctrine
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param MessageFactoryInterface $messageFactory
     */
    public function __construct(
        ManagerRegistry $doctrine,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        MessageFactoryInterface $messageFactory
    ) {
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param int $batchSize
     */
    public function setBatchSize($batchSize)
    {
        $batchSize = (int)$batchSize;
        if ($batchSize < 1) {
            $batchSize = self::BATCH_SIZE;
        }

        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageData = JSON::decode($message->getBody());

        $entityClass = $this->messageFactory->getEntityClassFromMessage($messageData);
        $createRedirect = $this->messageFactory->getCreateRedirectFromMessage($messageData);

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            sprintf('%s:%s', Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE, $entityClass),
            function (JobRunner $jobRunner) use ($entityClass, $createRedirect) {
                /** @var EntityManager $em */
                if (!$em = $this->doctrine->getManagerForClass($entityClass)) {
                    $this->logger->error(
                        sprintf('Entity manager is not defined for class: "%s"', $entityClass)
                    );

                    return false;
                }

                $identifierFieldName = $em->getClassMetadata($entityClass)
                    ->getSingleIdentifierFieldName();
                $repository = $em->getRepository($entityClass);
                $entityCount = $repository->createQueryBuilder('entity')
                    ->select('COUNT(entity)')
                    ->getQuery()
                    ->getSingleScalarResult();

                $batches = (int)ceil($entityCount / $this->batchSize);
                for ($i = 0; $i < $batches; $i++) {
                    $jobRunner->createDelayed(
                        sprintf('%s:%s:%s', Topics::JOB_GENERATE_DIRECT_URL_FOR_ENTITIES, $entityClass, $i),
                        function (
                            JobRunner $jobRunner,
                            Job $child
                        ) use (
                            $entityClass,
                            $createRedirect,
                            $i,
                            $repository,
                            $identifierFieldName
                        ) {
                            $message = $this->messageFactory->createMassMessage(
                                $entityClass,
                                $this->getEntityIds($repository, $identifierFieldName, $i),
                                $createRedirect
                            );
                            $message['jobId'] = $child->getId();

                            $this->producer->send(Topics::JOB_GENERATE_DIRECT_URL_FOR_ENTITIES, $message);
                        }
                    );
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param EntityRepository $repository
     * @param string $identifierFieldName
     * @param int $page
     * @return array|int[]
     */
    protected function getEntityIds(EntityRepository $repository, $identifierFieldName, $page)
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE];
    }
}
