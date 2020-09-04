<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\VisibilityBundle\Model\ProductMessageFactory;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Resolves visibility by Product entity
 */
class ProductProcessor implements MessageProcessorInterface
{
    /**
     * @var ManagerRegistry
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
     * @var string
     */
    protected $resolvedVisibilityClassName = '';

    /**
     * @var ProductReindexManager|null
     */
    private $productReindexManager;

    /**
     * @param ManagerRegistry $registry
     * @param ProductMessageFactory $messageFactory
     * @param LoggerInterface $logger
     * @param CacheBuilderInterface $cacheBuilder
     */
    public function __construct(
        ManagerRegistry $registry,
        ProductMessageFactory $messageFactory,
        LoggerInterface $logger,
        CacheBuilderInterface $cacheBuilder
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->messageFactory = $messageFactory;
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * @param ProductReindexManager|null $productReindexManager
     */
    public function setProductReindexManager(?ProductReindexManager $productReindexManager): void
    {
        $this->productReindexManager = $productReindexManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (!isset($body['id'])) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            if ($this->cacheBuilder instanceof ProductCaseCacheBuilderInterface) {
                $productIds = array_unique((array) $body['id']);

                $this->resolveProductsVisibility($productIds);

                if ($this->productReindexManager && $this->isCacheBuilderReindexCanBeDisabled()) {
                    $this->productReindexManager->reindexProducts($productIds);
                }
            }
            $em->commit();
        } catch (InvalidArgumentException $e) {
            $em->rollback();
            $this->logger->error(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Product Visibility resolve by Product',
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
     * @return bool
     */
    private function isCacheBuilderReindexCanBeDisabled(): bool
    {
        return is_callable([$this->cacheBuilder, 'productsCategoryChangedWithDisabledReindex']);
    }

    /**
     * @param int[] $productIds
     */
    private function resolveProductsVisibility(array $productIds): void
    {
        $products = $this->getProducts($productIds);

        if ($this->isCacheBuilderReindexCanBeDisabled()) {
            $this->cacheBuilder->productsCategoryChangedWithDisabledReindex($products);
        } else {
            foreach ($products as $product) {
                $this->cacheBuilder->productCategoryChanged($product);
            }
        }
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

    /**
     * @param int[] $productIds
     *
     * @return \Oro\Bundle\ProductBundle\Entity\Product[]
     *
     * @throws EntityNotFoundException If all products have not been found
     */
    private function getProducts(array $productIds): array
    {
        /** @var Product[] $products */
        $products = $this->registry->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->findBy(['id' => $productIds]);
        if (count($productIds) !== count($products)) {
            $foundIds = array_map(
                static function (Product $product) {
                    return $product->getId();
                },
                $products
            );
            $notFoundProductsIds = array_diff($productIds, $foundIds);
            $this->logger->warning(
                'The following products have not been not found when trying to resolve visibility',
                $notFoundProductsIds
            );

            if (!$products) {
                throw new EntityNotFoundException(
                    sprintf(
                        'Products have not been found when trying to resolve visibility: %s',
                        implode(', ', $notFoundProductsIds)
                    )
                );
            }
        }

        return $products;
    }
}
