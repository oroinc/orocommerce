<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\Topic\SyncSlugRedirectsTopic;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Updates scopes of Redirects by Slug id
 */
class SyncSlugRedirectsProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;

        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageData = $message->getBody();

        /** @var Slug $slug */
        $slug = $this->managerRegistry
            ->getRepository(Slug::class)
            ->find($messageData[SyncSlugRedirectsTopic::SLUG_ID]);

        // Slug not found, do nothing
        if (!$slug) {
            $this->logger->info('Slug #{slugId} is not found.', $messageData);

            return self::REJECT;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass(Redirect::class);
        /** @var Redirect[] $redirects */
        $redirects = $entityManager->getRepository(Redirect::class)->findBy(['slug' => $slug]);
        // No redirects found, nothing to do.
        if (!$redirects) {
            $this->logger->info(
                'Nothing to synchronize for slug #{slugId}: redirects are not found.',
                $messageData + ['slug' => $slug]
            );

            return self::REJECT;
        }

        foreach ($redirects as $redirect) {
            $redirect->setScopes($slug->getScopes());
        }

        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during scopes update of Redirects by Slug',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [SyncSlugRedirectsTopic::getName()];
    }
}
