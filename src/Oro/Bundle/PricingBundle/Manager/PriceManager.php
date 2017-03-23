<?php

namespace Oro\Bundle\PricingBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PostFlushEventArgs;
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
        $this->pricesToSave[] = $price;
    }

    /**
     * @param ProductPrice $price
     */
    protected function doSave(ProductPrice $price)
    {
        $class = ClassUtils::getRealClass(get_class($price));
        /** @var ProductPriceRepository $repository */
        $repository = $this->shardManager->getEntityManager()->getRepository($class);
        $repository->save($this->shardManager, $price);
        $event = new ProductPriceSaveAfterEvent($price);
        $this->eventDispatcher->dispatch(ProductPriceSaveAfterEvent::NAME, $event);
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
}
