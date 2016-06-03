<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;
use OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;

class CombinedProductPriceQueueConsumer
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var CombinedProductPriceResolver
     */
    protected $resolver;

    /**
     * @var ProductPriceChangeTriggerRepository
     */
    protected $queueRepository;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListRepository;

    /**
     * @var string
     */
    protected $productPriceChangeTriggerClass = 'OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger';

    /**
     * @var string
     */
    protected $combinedPriceListClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList';

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param ManagerRegistry $registry
     * @param CombinedProductPriceResolver $resolver
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ManagerRegistry $registry,
        CombinedProductPriceResolver $resolver,
        EventDispatcherInterface $dispatcher
    ) {
        $this->registry = $registry;
        $this->resolver = $resolver;
        $this->dispatcher = $dispatcher;
    }

    public function process()
    {
        foreach ($this->getQueueRepository()->getProductPriceChangeTriggersIterator() as $changes) {
            $this->handleProductPriceJob($changes);
            $this->getManager()->remove($changes);
        }
        $this->getManager()->flush();
    }

    /**
     * @param string $productPriceChangeTriggerClass
     */
    public function setProductPriceChangeTriggerClass($productPriceChangeTriggerClass)
    {
        $this->productPriceChangeTriggerClass = $productPriceChangeTriggerClass;
    }

    /**
     * @param string $combinedPriceListClass
     */
    public function setCombinedPriceListClass($combinedPriceListClass)
    {
        $this->combinedPriceListClass = $combinedPriceListClass;
    }

    /**
     * @param ProductPriceChangeTrigger $changes
     */
    protected function handleProductPriceJob(ProductPriceChangeTrigger $changes)
    {
        $repository = $this->getCombinedPriceListRepository();
        $iterator = $repository->getCombinedPriceListsByPriceList(
            $changes->getPriceList(),
            true
        );
        $builtCPLs = [];
        foreach ($iterator as $combinedPriceList) {
            $this->resolver->combinePrices($combinedPriceList, $changes->getProduct());
            $builtCPLs[$combinedPriceList->getId()] = true;
        }
        if ($builtCPLs) {
            $this->dispatchEvent($builtCPLs);
        }
    }

    /**
     * @return ObjectManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->registry->getManagerForClass($this->productPriceChangeTriggerClass);
        }

        return $this->manager;
    }

    /**
     * @return ProductPriceChangeTriggerRepository
     */
    protected function getQueueRepository()
    {
        if (!$this->queueRepository) {
            $this->queueRepository = $this->getManager()->getRepository($this->productPriceChangeTriggerClass);
        }

        return $this->queueRepository;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRepository()
    {
        if (!$this->combinedPriceListRepository) {
            $this->combinedPriceListRepository = $this->registry
                ->getManagerForClass($this->combinedPriceListClass)
                ->getRepository($this->combinedPriceListClass);
        }

        return $this->combinedPriceListRepository;
    }

    /**
     * @param array $cplIds
     */
    protected function dispatchEvent(array $cplIds)
    {
        $event = new CombinedPriceListsUpdateEvent(array_keys($cplIds));
        $this->dispatcher->dispatch(CombinedPriceListsUpdateEvent::NAME, $event);
    }
}
