<?php

namespace Oro\Bundle\AccountBundle\Async\Visibility;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Bundle\CatalogBundle\Event\AfterProductRecalculateVisibility;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Model\ProductMessageFactory;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductProcessor implements MessageProcessorInterface
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var ProductMessageFactory
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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var string
     */
    protected $resolvedVisibilityClassName = '';

    /**
     * @param RegistryInterface $registry
     * @param ProductMessageFactory $messageFactory
     * @param LoggerInterface $logger
     * @param CacheBuilderInterface $cacheBuilder
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        RegistryInterface $registry,
        ProductMessageFactory $messageFactory,
        LoggerInterface $logger,
        CacheBuilderInterface $cacheBuilder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->messageFactory = $messageFactory;
        $this->cacheBuilder = $cacheBuilder;
        $this->eventDispatcher = $eventDispatcher;
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
            $visibilityEntity = $this->messageFactory->getProductFromMessage($messageData);

            $this->resolveVisibilityByEntity($visibilityEntity);
            $em->commit();
            $event = new AfterProductRecalculateVisibility($visibilityEntity);
            $this->eventDispatcher->dispatch(AfterProductRecalculateVisibility::NAME, $event);
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
     * @param object|Product $entity
     */
    protected function resolveVisibilityByEntity($entity)
    {
        if ($this->cacheBuilder instanceof ProductCaseCacheBuilderInterface) {
            $this->cacheBuilder->productCategoryChanged($entity);
        }
    }
}
