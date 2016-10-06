<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DatabaseExceptionHelper;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\VisibilityBundle\Model\MessageFactoryInterface;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

abstract class AbstractVisibilityProcessor implements MessageProcessorInterface
{
    /**
     * @var ManagerRegistry
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
     * @var DatabaseExceptionHelper
     */
    protected $databaseExceptionHelper;

    /**
     * @var string
     */
    protected $resolvedVisibilityClassName = '';

    /**
     * @param ManagerRegistry $registry
     * @param MessageFactoryInterface $messageFactory
     * @param LoggerInterface $logger
     * @param CacheBuilderInterface $cacheBuilder
     * @param DatabaseExceptionHelper $databaseExceptionHelper
     */
    public function __construct(
        ManagerRegistry $registry,
        MessageFactoryInterface $messageFactory,
        LoggerInterface $logger,
        CacheBuilderInterface $cacheBuilder,
        DatabaseExceptionHelper $databaseExceptionHelper
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->messageFactory = $messageFactory;
        $this->cacheBuilder = $cacheBuilder;
        $this->databaseExceptionHelper = $databaseExceptionHelper;
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
                'Unexpected exception occurred during Product Visibility resolve',
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
