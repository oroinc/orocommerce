<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
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
     * @param ManagerRegistry $registry
     * @param CombinedProductPriceResolver $resolver
     */
    public function __construct(ManagerRegistry $registry, CombinedProductPriceResolver $resolver)
    {
        $this->registry = $registry;
        $this->resolver = $resolver;
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
        foreach ($iterator as $combinedPriceList) {
            $this->resolver->combinePrices($combinedPriceList, $changes->getProduct());
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
}
