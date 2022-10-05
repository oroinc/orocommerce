<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\Common\Cache\FlushableCache;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Generate Slug URLs for given entities
 */
class DirectUrlProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $registry;

    private SlugEntityGenerator $generator;

    private MessageFactoryInterface $messageFactory;

    private UrlCacheInterface $urlCache;

    private ?SluggableUrlDumper $urlCacheDumper = null;

    public function __construct(
        ManagerRegistry $registry,
        SlugEntityGenerator $generator,
        MessageFactoryInterface $messageFactory,
        LoggerInterface $logger,
        UrlCacheInterface $urlCache
    ) {
        $this->registry = $registry;
        $this->generator = $generator;
        $this->messageFactory = $messageFactory;
        $this->logger = $logger;
        $this->urlCache = $urlCache;
    }

    public function setUrlCacheDumper(SluggableUrlDumper $urlCacheDumper): void
    {
        $this->urlCacheDumper = $urlCacheDumper;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $em = null;
        try {
            $messageData = $message->getBody();
            $className = $this->messageFactory->getEntityClassFromMessage($messageData);
            $entities = $this->messageFactory->getEntitiesFromMessage($messageData);
            $createRedirect = $this->messageFactory->getCreateRedirectFromMessage($messageData);

            /** @var EntityManagerInterface $em */
            $em = $this->registry->getManagerForClass($className);
            $em->beginTransaction();
            foreach ($entities as $entity) {
                $this->generator->generateWithoutCacheDump($entity, $createRedirect);
            }

            $em->flush();
            $em->commit();
            $this->actualizeUrlCache($entities);
        } catch (UniqueConstraintViolationException $e) {
            if ($em && $em->getConnection()->getTransactionNestingLevel() > 0) {
                $em->rollback();
            }

            return self::REQUEUE;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during Direct URL generation',
                ['exception' => $e]
            );

            if ($em && $em->getConnection()->getTransactionNestingLevel() > 0) {
                $em->rollback();
            }

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }

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

        if ($this->urlCache instanceof FlushableCache) {
            $this->urlCache->flushAll();
        }
    }
}
