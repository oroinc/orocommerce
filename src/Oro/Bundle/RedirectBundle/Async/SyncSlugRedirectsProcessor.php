<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\DBAL\Driver\DriverException;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

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
     * @var DatabaseExceptionHelper
     */
    protected $databaseExceptionHelper;

    /**
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $logger,
        DatabaseExceptionHelper $databaseExceptionHelper
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->databaseExceptionHelper = $databaseExceptionHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageData = JSON::decode($message->getBody());

        if (!array_key_exists('slugId', $messageData)) {
            $this->logger->critical(
                'Message is invalid. Key "slugId" is missing from message data.',
                ['message' => $message]
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

            if ($e instanceof DriverException && $this->databaseExceptionHelper->isDeadlock($e)) {
                return self::REQUEUE;
            } else {
                return self::REJECT;
            }
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::GENERATE_SLUG_REDIRECTS];
    }
}
