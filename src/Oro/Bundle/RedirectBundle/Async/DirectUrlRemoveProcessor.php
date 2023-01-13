<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheMassTopic;
use Oro\Bundle\RedirectBundle\Async\Topic\RemoveDirectUrlForEntityTypeTopic;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Model\DirectUrlMessageFactory;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Removes Slug URLs for the entities of specified type.
 */
class DirectUrlRemoveProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $managerRegistry;

    private MessageProducerInterface $messageProducer;

    public function __construct(
        ManagerRegistry $managerRegistry,
        MessageProducerInterface $messageProducer
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->messageProducer = $messageProducer;

        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $entityManager = null;
        try {
            $entityClass = $message->getBody();
            $entityManager = $this->managerRegistry->getManagerForClass($entityClass);

            $entityManager->beginTransaction();
            $entityManager
                ->getRepository(Slug::class)
                ->deleteSlugAttachedToEntityByClass($entityClass);
            $entityManager->commit();

            $this->messageProducer->send(
                CalculateSlugCacheMassTopic::getName(),
                [DirectUrlMessageFactory::ENTITY_CLASS_NAME => $entityClass, DirectUrlMessageFactory::ID => []]
            );
        } catch (\Exception $e) {
            $entityManager?->rollback();

            $this->logger->error(
                'Unexpected exception occurred during Direct URL removal',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        return self::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [RemoveDirectUrlForEntityTypeTopic::getName()];
    }
}
