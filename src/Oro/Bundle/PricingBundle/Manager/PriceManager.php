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
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages product prices.
 */
class PriceManager
{
    /** @var ShardManager */
    protected $shardManager;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var MessageBufferManager */
    protected $messageBufferManager;

    /** @var ProductPrice[] */
    protected $pricesToSave = [];

    /** @var ProductPrice[] */
    protected $pricesToRemove = [];

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
        $this->pricesToSave[] = $price;
    }

    public function remove(ProductPrice $price)
    {
        $this->pricesToRemove[] = $price;
    }

    protected function doSave(ProductPrice $price)
    {
        $price->updatePrice();
        $class = ClassUtils::getRealClass(get_class($price));

        $em = $this->shardManager->getEntityManager();
        $classMetadata = $em->getClassMetadata(ProductPrice::class);
        $uow = $em->getUnitOfWork();
        $changeSet = $this->getChangeSet($uow, $classMetadata, $price);

        if ($price->getId() === null || !empty($changeSet)) {
            /** @var ProductPriceRepository $repository */
            $repository = $em->getRepository($class);

            //remove price from old shard
            if (array_key_exists('priceList', $changeSet) && $changeSet['priceList'][0]) {
                $newPriceList = $price->getPriceList();
                $price->setPriceList($changeSet['priceList'][0]);
                $this->doRemove($price);
                $price = clone $price;
                $price->setPriceList($newPriceList);
                $changeSet = $this->getChangeSet($uow, $classMetadata, $price);
            }
            $repository->save($this->shardManager, $price);

            if ($price->getId() && !$uow->isInIdentityMap($price)) {
                $uow->registerManaged($price, ['id' => $price->getId()], $changeSet);
            }

            $changeSet = $this->getChangeSet($uow, $classMetadata, $price);

            $this->eventDispatcher->dispatch(
                new ProductPriceSaveAfterEvent(new PreUpdateEventArgs($price, $em, $changeSet)),
                ProductPriceSaveAfterEvent::NAME
            );
        }
    }

    protected function doRemove(ProductPrice $price)
    {
        $class = ClassUtils::getRealClass(get_class($price));

        $em = $this->shardManager->getEntityManager();

        /** @var ProductPriceRepository $repository */
        $repository = $em->getRepository($class);
        $repository->remove($this->shardManager, $price);

        $event = new ProductPriceRemove($price);
        $event->setEntityManager($em);
        $this->eventDispatcher->dispatch($event, ProductPriceRemove::NAME);

        $em->detach($price);
    }

    public function flush()
    {
        $pricesToRemove = $this->pricesToRemove;
        $pricesToSave = $this->pricesToSave;
        $this->pricesToRemove = [];
        $this->pricesToSave = [];
        foreach ($pricesToRemove as $price) {
            $this->doRemove($price);
        }
        foreach ($pricesToSave as $price) {
            $this->doSave($price);
        }
        if ($pricesToRemove || $pricesToSave) {
            $event = new ProductPricesUpdated();
            $event->setEntityManager($this->shardManager->getEntityManager());
            $this->eventDispatcher->dispatch($event, ProductPricesUpdated::NAME);

            // do flushing the message buffer here because the flush() does not use a database transaction
            // and can be executed without an outer database transaction
            $this->messageBufferManager->flushBuffer();
        }
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

    /**
     * @return ObjectManager|EntityManager
     */
    public function getEntityManager()
    {
        return $this->shardManager->getEntityManager();
    }
}
