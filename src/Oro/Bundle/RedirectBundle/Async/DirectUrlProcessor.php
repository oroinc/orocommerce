<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Generate Slug URLs for given entities
 */
class DirectUrlProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const string SQLSTATE_UNIQUE_VIOLATION = '23505';

    private ManagerRegistry $registry;

    private SlugEntityGenerator $generator;

    private MessageFactoryInterface $messageFactory;

    private UrlCacheInterface $urlCache;

    private SluggableUrlDumper $urlCacheDumper;

    public function __construct(
        ManagerRegistry $registry,
        SlugEntityGenerator $generator,
        MessageFactoryInterface $messageFactory,
        UrlCacheInterface $urlCache,
        SluggableUrlDumper $urlCacheDumper
    ) {
        $this->registry = $registry;
        $this->generator = $generator;
        $this->messageFactory = $messageFactory;
        $this->urlCache = $urlCache;
        $this->urlCacheDumper = $urlCacheDumper;

        $this->logger = new NullLogger();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        try {
            $messageData = $message->getBody();
            $className = $this->messageFactory->getEntityClassFromMessage($messageData);
            $entities = $this->messageFactory->getEntitiesFromMessage($messageData);
            $createRedirect = $this->messageFactory->getCreateRedirectFromMessage($messageData);
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during Direct URL generation',
                ['exception' => $e]
            );

            return $e instanceof RetryableException ? self::REQUEUE : self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass($className);

        $processedEntities = [];
        foreach ($entities as $entity) {
            try {
                $em->beginTransaction();
                $this->generator->generateWithoutCacheDump($entity, $createRedirect);
                $em->flush();
                $em->commit();
                $processedEntities[] = $entity;
            } catch (\Exception $e) {
                $isDuplicate = $this->isUniqueConstraintViolation($e);
                $this->rollbackAndReset($em);

                if ($isDuplicate) {
                    // Another entity or consumer already committed a slug with the same hash.
                    $this->logger->warning(
                        'Unique constraint violation generating a Direct URL — requeueing',
                        ['exception' => $e]
                    );

                    return self::REQUEUE;
                }

                $this->logger->error(
                    'Unexpected exception occurred during Direct URL generation',
                    ['exception' => $e]
                );

                return $e instanceof RetryableException ? self::REQUEUE : self::REJECT;
            }
        }

        if ($processedEntities !== []) {
            $this->actualizeUrlCache($processedEntities);
        }

        return self::ACK;
    }

    /**
     * The unique index on the slug table is deferrable and only checked at COMMIT time, at which
     * point Doctrine surfaces a raw driver exception instead of a typed UniqueConstraintViolationException.
     */
    private function isUniqueConstraintViolation(\Exception $e): bool
    {
        if ($e instanceof UniqueConstraintViolationException) {
            return true;
        }

        if ($e instanceof DriverException) {
            return $e->getSQLState() === self::SQLSTATE_UNIQUE_VIOLATION;
        }

        return false;
    }

    private function rollbackAndReset(EntityManagerInterface $em): void
    {
        try {
            if ($em->getConnection()->isTransactionActive()) {
                $em->rollback();
            }
        } catch (\Exception $e) {
            $this->logger->warning(
                'Rollback failed after exception — transaction was likely already closed',
                ['exception' => $e]
            );
        }

        // Always reset: a rolled-back flush can leave the identity map holding entities with
        // auto-generated ids that no longer exist in the database, breaking any retry.
        $this->registry->resetManager();
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [GenerateDirectUrlForEntitiesTopic::getName()];
    }

    private function actualizeUrlCache(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->urlCacheDumper->dump($entity);
        }

        // Remove slug routes cache on Slug changes to refill it with actual data
        $this->urlCache->removeUrl(UrlCacheInterface::SLUG_ROUTES_KEY, []);

        if ($this->urlCache instanceof FlushableCacheInterface) {
            $this->urlCache->flushAll();
        }
    }
}
