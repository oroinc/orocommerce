<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\Doctrine;

use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Listener create and update product suggestions based on SKU and product names field.
 * listen also product status changes, if changed then recreate
 */
class CreateProductSuggestionListener implements OptionalListenerInterface, FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;
    use OptionalListenerTrait;

    /** @var \SplObjectStorage<Product> */
    private \SplObjectStorage $products;

    public function __construct(
        private MessageProducerInterface $producer,
        private array $updateFields,
        private int $batchSize
    ) {
        $this->products = new \SplObjectStorage();
    }

    public function onFlush(ManagerEventArgs $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $em  = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->processInsertion($entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->processUpdate($entity, $uow);
        }
    }

    public function postFlush(ManagerEventArgs $args): void
    {
        if (empty($this->products) || !$this->isEnabled()) {
            return;
        }

        $productsIds = [];

        foreach ($this->products as $product) {
            $productsIds[] = $product->getId();
            $this->products->detach($product);
        }

        foreach (array_chunk($productsIds, $this->batchSize) as $idsChunk) {
            $this->producer->send(
                GenerateSuggestionsTopic::getName(),
                [GenerateSuggestionsTopic::PRODUCT_IDS => $idsChunk]
            );
        }
    }

    private function processInsertion(object $entity): void
    {
        if ($entity instanceof Product && $entity->getSku()) {
            $this->rememberProduct($entity);
        }

        if ($entity instanceof ProductName && $product = $entity->getProduct()) {
            $this->rememberProduct($product);
        }
    }

    private function processUpdate(object $entity, UnitOfWork $unitOfWork): void
    {
        if ($entity instanceof Product) {
            $changeSet = $unitOfWork->getEntityChangeSet($entity);
            if (array_intersect(array_keys($changeSet), $this->updateFields)) {
                $this->rememberProduct($entity);
            }
        }

        if ($entity instanceof ProductName) {
            $this->rememberProduct($entity->getProduct());
        }
    }

    private function rememberProduct(Product $product): void
    {
        $this->products->attach($product);
    }

    private function isEnabled(): bool
    {
        return $this->enabled && $this->isFeaturesEnabled();
    }
}
