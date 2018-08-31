<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Bundle\RedirectBundle\Provider\SluggableUrlDatabaseAwareProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Generates direct URLs
 */
class DirectUrlProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var SlugEntityGenerator
     */
    private $generator;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var DatabaseExceptionHelper
     */
    private $databaseExceptionHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlCacheInterface
     */
    private $urlCache;

    /**
     * @param ManagerRegistry $registry
     * @param SlugEntityGenerator $generator
     * @param MessageFactoryInterface $messageFactory
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     * @param LoggerInterface $logger
     * @param UrlCacheInterface $urlCache
     */
    public function __construct(
        ManagerRegistry $registry,
        SlugEntityGenerator $generator,
        MessageFactoryInterface $messageFactory,
        DatabaseExceptionHelper $databaseExceptionHelper,
        LoggerInterface $logger,
        UrlCacheInterface $urlCache
    ) {
        $this->registry = $registry;
        $this->generator = $generator;
        $this->messageFactory = $messageFactory;
        $this->logger = $logger;
        $this->databaseExceptionHelper = $databaseExceptionHelper;
        $this->urlCache = $urlCache;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $em = null;
        try {
            $messageData = JSON::decode($message->getBody());
            $className =  $this->messageFactory->getEntityClassFromMessage($messageData);
            /** @var EntityManagerInterface $em */
            $em = $this->registry->getManagerForClass($className);
            $em->beginTransaction();

            $entities = $this->messageFactory->getEntitiesFromMessage($messageData);
            $createRedirect = $this->messageFactory->getCreateRedirectFromMessage($messageData);
            foreach ($entities as $entity) {
                $this->generator->generate($entity, $createRedirect);
            }

            $em->flush();
            $em->commit();

            $this->urlCache->removeUrl(SluggableUrlDatabaseAwareProvider::SLUG_ROUTES_KEY, []);

            if ($this->urlCache instanceof FlushableCache) {
                $this->urlCache->flushAll();
            }
        } catch (InvalidArgumentException $e) {
            if ($em) {
                $em->rollback();
            }
            $this->logger->error(
                'Queue Message is invalid',
                ['exception' => $e]
            );

            return self::REJECT;
        } catch (\Exception $e) {
            if ($em) {
                $em->rollback();
            }
            $this->logger->error(
                'Unexpected exception occurred during Direct URL generation',
                ['exception' => $e]
            );

            $driverException = $this->databaseExceptionHelper->getDriverException($e);
            if ($driverException && $this->databaseExceptionHelper->isDeadlock($driverException)) {
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
        return [
            Topics::GENERATE_DIRECT_URL_FOR_ENTITIES
        ];
    }
}
