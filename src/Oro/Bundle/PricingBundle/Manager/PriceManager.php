<?php

namespace Oro\Bundle\PricingBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MessageQueueBundle\Client\MessageBufferManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdated;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdatedAfter;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages product prices.
 */
class PriceManager
{
    private ShardManager $shardManager;
    private EventDispatcherInterface $eventDispatcher;
    private MessageBufferManager $messageBufferManager;
    private array $pricesToSave = [];
    private array $pricesToRemove = [];
    private array $pricesToUpdate = [];

    public function __construct(
        ShardManager $shardManager,
        EventDispatcherInterface $eventDispatcher,
        MessageBufferManager $messageBufferManager
    ) {
        $this->shardManager = $shardManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->messageBufferManager = $messageBufferManager;
    }

    public function persist(ProductPrice $price)
    {
        if ($price->getId()) {
            $this->pricesToUpdate[] = $price;
        } else {
            $this->pricesToSave[] = $price;
        }
    }

    public function remove(ProductPrice $price)
    {
        $this->pricesToRemove[] = $price;
    }

    protected function doSave(ProductPrice $price)
    {
        $removePrice = null;
        $price->updatePrice();

        $em = $this->getEntityManager();
        $classMetadata = $em->getClassMetadata(ProductPrice::class);
        $uow = $em->getUnitOfWork();
        $changeSet = $this->getChangeSet($uow, $classMetadata, $price);

        if ($price->getId() === null || !empty($changeSet)) {
            $repository = $this->getProductPriceRepository();

            if (array_key_exists('priceList', $changeSet) && $changeSet['priceList'][0]) {
                // Updating the price list is an equivalent operation to removing the price with the price list
                // and adding a new price with the price list.
                [$removePrice, $price] = $this->removePriceListFromOldShard($price, $changeSet);
            } else {
                $repository->save($this->shardManager, $price);
            }

            if ($price->getId() && !$uow->isInIdentityMap($price)) {
                $uow->registerManaged($price, ['id' => $price->getId()], $changeSet);
                $changeSet = $this->getChangeSet($uow, $classMetadata, $price);
            }
        }

        return [
            'changeSet' => $changeSet,
            'removedPrice' => $removePrice,
            'savedPrice' => $price
        ];
    }

    private function removePriceListFromOldShard(ProductPrice $price, array $changeSet): array
    {
        $savedPrice = clone $price;
        $price->setPriceList($changeSet['priceList'][0]);

        return [$price, $savedPrice];
    }

    private function getProductPriceRepository(): ProductPriceRepository
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->getRepository(ProductPrice::class);
    }

    protected function doRemove(ProductPrice $price)
    {
        $class = ClassUtils::getRealClass(get_class($price));

        $em = $this->getEntityManager();

        /** @var ProductPriceRepository $repository */
        $repository = $em->getRepository($class);
        $repository->remove($this->shardManager, $price);

        $em->detach($price);
    }

    public function flush()
    {
        $changeSets = [];
        $pricesToRemove = $this->pricesToRemove;
        $pricesToUpdate = $this->pricesToUpdate;
        $pricesToSave = $this->pricesToSave;
        $this->clearState();

        $this->flushRemovedPrices($pricesToRemove);
        $this->flushUpdatedPrices($pricesToRemove, $pricesToSave, $pricesToUpdate, $changeSets);
        $this->flushSavedPrices($pricesToSave);

        if ($pricesToRemove || $pricesToSave || $pricesToUpdate) {
            $this->dispatchRemovedPrices($pricesToRemove);
            $this->dispatchSavedPrices(array_merge($pricesToSave, $pricesToUpdate), $changeSets);
            $this->dispatchUpdatedPrices($pricesToRemove, $pricesToSave, $pricesToUpdate, $changeSets);

            $this->dispatchUpdatePricesAfter($pricesToRemove, $pricesToSave, $pricesToUpdate, $changeSets);
            // do flushing the message buffer here because the flush() does not use a database transaction
            // and can be executed without an outer database transaction
            $this->messageBufferManager->flushBuffer();
        }
    }

    private function flushUpdatedPrices(
        array &$pricesToRemove,
        array &$pricesToSave,
        array &$pricesToUpdate,
        array &$changeSets
    ): void {
        foreach ($pricesToUpdate as $key => $price) {
            $savedData = $this->doSave($price);
            if (isset($savedData['removedPrice']) && isset($savedData['savedPrice'])) {
                $pricesToRemove[] = $savedData['removedPrice'];
                $pricesToSave[] = $savedData['savedPrice'];
                unset($pricesToUpdate[$key]);
                continue;
            }

            if (empty($savedData['changeSet'])) {
                unset($pricesToUpdate[$key]);
                continue;
            }

            $changeSets[$price->getId()] = $savedData['changeSet'];
        }
    }

    private function flushRemovedPrices(array $pricesToRemove): void
    {
        foreach ($pricesToRemove as $price) {
            $this->doRemove($price);
        }
    }

    private function flushSavedPrices(array $pricesToSave): void
    {
        foreach ($pricesToSave as $price) {
            $this->doSave($price);
        }
    }

    private function dispatchUpdatedPrices(
        array $pricesToRemove,
        array $pricesToSave,
        array $pricesToUpdate,
        array $changeSets
    ): void {
        $event = new ProductPricesUpdated(
            $this->getEntityManager(),
            $pricesToRemove,
            $pricesToSave,
            $pricesToUpdate,
            $changeSets
        );
        $this->eventDispatcher->dispatch($event, ProductPricesUpdated::NAME);
    }

    private function dispatchRemovedPrices(array $pricesToRemove): void
    {
        $em = $this->getEntityManager();

        /** @var ProductPrice[] $priceToRemove */
        foreach ($pricesToRemove as $priceToRemove) {
            $event = new ProductPriceRemove($priceToRemove);
            $event->setEntityManager($em);
            $this->eventDispatcher->dispatch($event, ProductPriceRemove::NAME);
        }
    }

    private function dispatchSavedPrices(array $pricesToSave, array $changeSets): void
    {
        $em = $this->getEntityManager();

        /** @var ProductPrice[] $pricesToSave */
        foreach ($pricesToSave as $price) {
            $changeSet = array_key_exists($price->getId(), $changeSets) ? $changeSets[$price->getId()] : [];
            $event = new PreUpdateEventArgs($price, $em, $changeSet);
            $this->eventDispatcher->dispatch(new ProductPriceSaveAfterEvent($event), ProductPriceSaveAfterEvent::NAME);
        }
    }

    private function dispatchUpdatePricesAfter(
        array $pricesToRemove,
        array $pricesToSave,
        array $pricesToUpdate,
        array $changeSets
    ): void {
        $em = $this->getEntityManager();
        $event = new ProductPricesUpdatedAfter($em, $pricesToRemove, $pricesToSave, $pricesToUpdate, $changeSets);
        $this->eventDispatcher->dispatch($event, ProductPricesUpdatedAfter::NAME);
    }

    /**
     * @param UnitOfWork $uow
     * @param ClassMetadata $classMetadata
     * @param ProductPrice $price
     * @return array
     */
    private function getChangeSet(UnitOfWork $uow, ClassMetadata $classMetadata, ProductPrice $price)
    {
        if ($price->getId()) {
            /**
             * Change the data type of the price value in the original data of UOW to the float to make it
             * consistent with the ProductPrice entity if the price value is the float there.
             * It is required because in some cases the {@see \Symfony\Component\Form\Extension\Core\Type\NumberType}
             * form type is used to handle the price value field and this form type converts the value to a float
             * and as result comparison of the product prices can work incorrect.
             * @see \Oro\Bundle\PricingBundle\Entity\EntityListener\BaseProductPriceEntityListener::isPriceValueChanged
             */
            $originalData = $uow->getOriginalEntityData($price);
            if (!empty($originalData['value'])
                && !\is_float($originalData['value'])
                && null !== $price->getPrice()
                && \is_float($price->getPrice()->getValue())
            ) {
                $originalData['value'] = (float)$originalData['value'];
                $uow->setOriginalEntityData($price, $originalData);
            }

            $uow->computeChangeSet($classMetadata, $price);
        }

        return $uow->getEntityChangeSet($price);
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        $this->flush();
    }

    private function clearState(): void
    {
        $this->pricesToRemove = [];
        $this->pricesToSave = [];
        $this->pricesToUpdate = [];
    }

    /**
     * @return ObjectManager|EntityManager
     */
    public function getEntityManager()
    {
        return $this->shardManager->getEntityManager();
    }
}
