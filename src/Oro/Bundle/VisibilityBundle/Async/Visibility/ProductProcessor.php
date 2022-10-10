<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeProductCategoryTopic;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Resolves visibility for a product when its category is changed.
 */
class ProductProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ManagerRegistry $managerRegistry;

    private ProductCaseCacheBuilderInterface $cacheBuilder;

    public function __construct(ManagerRegistry $managerRegistry, ProductCaseCacheBuilderInterface $cacheBuilder)
    {
        $this->managerRegistry = $managerRegistry;
        $this->cacheBuilder = $cacheBuilder;
        $this->logger = new NullLogger();
    }

    public static function getSubscribedTopics(): array
    {
        return [VisibilityOnChangeProductCategoryTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();
        $entityManager = $this->managerRegistry->getManagerForClass(ProductVisibilityResolved::class);
        $entityManager->beginTransaction();
        try {
            $productIds = array_unique((array)$messageBody['id']);
            $products = $this->getProducts($productIds);
            foreach ($products as $product) {
                $this->cacheBuilder->productCategoryChanged($product, $messageBody['scheduleReindex']);
            }
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Product Visibility resolve by Product.',
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
     * @param int[] $productIds
     *
     * @return Product[]
     *
     * @throws EntityNotFoundException If all products have not been found
     */
    private function getProducts(array $productIds): array
    {
        $products = $this->managerRegistry
            ->getRepository(Product::class)
            ->findBy(['id' => $productIds]);
        if (count($productIds) !== count($products)) {
            $foundIds = array_map(static fn (Product $product) => $product->getId(), $products);
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
