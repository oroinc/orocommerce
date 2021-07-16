<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class DirectUrlRemoveProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger,
        MessageProducerInterface $producer
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $em = null;
        try {
            $entityClass = JSON::decode($message->getBody());

            /** @var EntityManager $em */
            if (!$em = $this->registry->getManagerForClass($entityClass)) {
                $this->logger->error(
                    sprintf('Entity manager is not defined for class: "%s"', $entityClass)
                );

                return self::REJECT;
            }
            /** @var SlugRepository $repository */
            $repository = $em->getRepository(Slug::class);

            $em->beginTransaction();
            $repository->deleteSlugAttachedToEntityByClass($entityClass);
            $em->commit();
            $this->producer->send(Topics::CALCULATE_URL_CACHE_MASS, '');
        } catch (\Exception $e) {
            if ($em) {
                $em->rollback();
            }
            $this->logger->error(
                'Unexpected exception occurred during Direct URL removal',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [
            Topics::REMOVE_DIRECT_URL_FOR_ENTITY_TYPE
        ];
    }
}
