<?php

namespace Oro\Bundle\PricingBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
        /** @var ProductPriceRepository $repository */
        $em = $this->shardManager->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();
        $classMetadata = $em->getClassMetadata(ProductPrice::class);
        $unitOfWork->computeChangeSet($classMetadata, $price);
        $changeSet = $unitOfWork->getEntityChangeSet($price);
        $repository = $em->getRepository($class);

        if ($price->getId() === null || !empty($changeSet)) {
            //remove price from old shard
            if (array_key_exists('priceList', $changeSet) && $changeSet['priceList'][0]) {
                $newPriceList = $price->getPriceList();
                $price->setPriceList($changeSet['priceList'][0]);
                $repository->remove($this->shardManager, $price);
                $price->setId(null);
                $price->setPriceList($newPriceList);
            }
            $repository->save($this->shardManager, $price);
            $args = new PreUpdateEventArgs($price, $em, $changeSet);
            $event = new ProductPriceSaveAfterEvent($args);
            $this->eventDispatcher->dispatch(ProductPriceSaveAfterEvent::NAME, $event);
        }
    }

    /**
     * @param ProductPrice $price
     */
    protected function doRemove(ProductPrice $price)
    {
        $class = ClassUtils::getRealClass(get_class($price));
        /** @var ProductPriceRepository $repository */
        $repository = $this->shardManager->getEntityManager()->getRepository($class);
        $repository->remove($this->shardManager, $price);

        $event = new ProductPriceRemove($price);
        $this->eventDispatcher->dispatch(ProductPriceRemove::NAME, $event);
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
