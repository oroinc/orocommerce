<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
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
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
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
