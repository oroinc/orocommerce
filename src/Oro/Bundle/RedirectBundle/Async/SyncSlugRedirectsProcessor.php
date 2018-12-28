<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updates scopes of Redirects by Slug id
 */
class SyncSlugRedirectsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

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
        $messageData = JSON::decode($message->getBody());

        if (!array_key_exists('slugId', $messageData)) {
            $this->logger->critical(
                'Message is invalid. Key "slugId" is missing from message data.'
            );

            return self::REJECT;
        }

        /** @var EntityManager $manager */
        $manager = $this->registry->getManagerForClass(Redirect::class);
        $manager->beginTransaction();
        try {
            /** @var Slug $slug */
            $slug = $this->registry->getManagerForClass(Slug::class)
                ->getRepository(Slug::class)
                ->find($messageData['slugId']);

            $slugScopes = $slug->getScopes();

            /** @var Redirect[] $redirects */
            $redirects = $manager->getRepository(Redirect::class)
                ->findBy(['slug' => $slug]);

            foreach ($redirects as $redirect) {
                $redirect->setScopes($slugScopes);
            }

            $manager->flush();
            $manager->commit();
        } catch (\Exception $e) {
            $manager->rollback();

            $this->logger->error(
                'Unexpected exception occurred during Deleting attribute relation',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SYNC_SLUG_REDIRECTS];
    }
}
