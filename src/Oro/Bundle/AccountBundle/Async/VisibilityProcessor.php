<?php

namespace Oro\Bundle\AccountBundle\Async;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\AccountBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class VisibilityProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var VisibilityMessageFactory
     */
    protected $messageFactory;

    /**
     * @var CacheBuilderInterface
     */
    protected $cacheBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RegistryInterface $registry
     * @param VisibilityMessageFactory $messageFactory
     * @param CacheBuilderInterface $cacheBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        RegistryInterface $registry,
        VisibilityMessageFactory $messageFactory,
        CacheBuilderInterface $cacheBuilder,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->cacheBuilder = $cacheBuilder;
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $messageData = JSON::decode($message->getBody());
            $visibilityEntity = $this->messageFactory->getVisibilityFromMessage($messageData);

            $this->cacheBuilder->resolveVisibilitySettings($visibilityEntity);
            $em->commit();
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                sprintf(
                    'Message is invalid: %s. Original message: "%s"',
                    $e->getMessage(),
                    $message->getBody()
                )
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                sprintf(
                    'Transaction aborted wit error: %s.',
                    $e->getMessage()
                )
            );

            return self::REQUEUE;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RESOLVE_PRODUCT_VISIBILITY];
    }

    /**
     * All resolved product visibility entities should be stored together, so entity manager should be the same too
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass(ProductVisibilityResolved::class);
    }
}
