<?php

namespace Oro\Bundle\CustomerBundle\Async\Visibility;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\CustomerBundle\Model\MessageFactoryInterface;
use Oro\Bundle\CustomerBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\CustomerBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class AbstractVisibilityProcessor implements MessageProcessorInterface
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
     * @var string
     */
    protected $resolvedVisibilityClassName = '';

    /**
     * @param RegistryInterface $registry
     * @param MessageFactoryInterface $messageFactory
     * @param LoggerInterface $logger
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function __construct(
        RegistryInterface $registry,
        MessageFactoryInterface $messageFactory,
        LoggerInterface $logger,
        CacheBuilderInterface $cacheBuilder
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->messageFactory = $messageFactory;
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $messageData = JSON::decode($message->getBody());
            $visibilityEntity = $this->messageFactory->getEntityFromMessage($messageData);

            $this->resolveVisibilityByEntity($visibilityEntity);
            $em->commit();
        } catch (InvalidArgumentException $e) {
            $em->rollback();
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
     * @param string $className
     */
    public function setResolvedVisibilityClassName($className)
    {
        $this->resolvedVisibilityClassName = $className;
    }

    /**
     * All resolved product visibility entities should be stored together, so entity manager should be the same too
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass($this->resolvedVisibilityClassName);
    }

    /**
     * @param object $entity
     */
    abstract protected function resolveVisibilityByEntity($entity);
}
