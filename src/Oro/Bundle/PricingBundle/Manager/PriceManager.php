<?php

namespace Oro\Bundle\PricingBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CurrencyBundle\Entity\Price;
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
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array|ProductPrice[]
     */
    protected $pricesToSave = [];

    /**
     * @var array|ProductPrice[]
     */
    protected $pricesToRemove = [];

    /**
     * @param ShardManager $shardManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ShardManager $shardManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->shardManager = $shardManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ProductPrice $price
     */
    public function persist(ProductPrice $price)
    {
        $this->pricesToSave[] = $price;
    }

    /**
     * @param ProductPrice $price
     */
    public function remove(ProductPrice $price)
    {
        $this->pricesToRemove[] = $price;
    }

    /**
     * @param ProductPrice $price
     */
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

            $args = new PreUpdateEventArgs($price, $em, $changeSet);
            $this->eventDispatcher->dispatch(ProductPriceSaveAfterEvent::NAME, new ProductPriceSaveAfterEvent($args));
        }
    }

    /**
     * @param ProductPrice $price
     */
    protected function doRemove(ProductPrice $price)
    {
        $class = ClassUtils::getRealClass(get_class($price));

        $em = $this->shardManager->getEntityManager();

        /** @var ProductPriceRepository $repository */
        $repository = $em->getRepository($class);
        $repository->remove($this->shardManager, $price);

        $event = new ProductPriceRemove($price);
        $event->setEntityManager($em);

        $this->eventDispatcher->dispatch(ProductPriceRemove::NAME, $event);

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

            $this->eventDispatcher->dispatch(ProductPricesUpdated::NAME, $event);
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
            $originalData = $uow->getOriginalEntityData($price);
            //small workaround to tell Doctrine that the price value didn't change
            if (!empty($originalData['value'])) {
                $originalData['value'] = (float) $originalData['value'];

                $uow->setOriginalEntityData($price, $originalData);
            }

            $uow->computeChangeSet($classMetadata, $price);
        }

        return $uow->getEntityChangeSet($price);
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $this->flush();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|\Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->shardManager->getEntityManager();
    }
}
