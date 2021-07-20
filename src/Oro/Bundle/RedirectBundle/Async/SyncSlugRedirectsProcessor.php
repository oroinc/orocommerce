<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        try {
            $messageData = $this->getResolvedMessageData(JSON::decode($message->getBody()));
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'Queue Message is invalid',
                ['exception' => $e]
            );

            return self::REJECT;
        }

        /** @var Slug $slug */
        $slug = $this->registry->getManagerForClass(Slug::class)
            ->getRepository(Slug::class)
            ->find($messageData['slugId']);

        // Slug not found, do nothing
        if (!$slug) {
            return self::REJECT;
        }

        /** @var EntityManager $manager */
        $manager = $this->registry->getManagerForClass(Redirect::class);
        /** @var Redirect[] $redirects */
        $redirects = $manager->getRepository(Redirect::class)->findBy(['slug' => $slug]);
        // No redirects found, nothing to do.
        if (!$redirects) {
            return self::REJECT;
        }

        foreach ($redirects as $redirect) {
            $redirect->setScopes($slug->getScopes());
        }

        try {
            $manager->flush();
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SYNC_SLUG_REDIRECTS];
    }

    private function getResolvedMessageData(array $message): array
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired(['slugId']);
        $optionsResolver->setAllowedTypes('slugId', ['integer', 'string']);

        return $optionsResolver->resolve($message);
    }
}
