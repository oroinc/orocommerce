<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ChangedProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;
use OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice;

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
     * @var ChangedProductPriceRepository
     */
    protected $queueRepository;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceList;

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
        foreach ($this->getQueueRepository()->getCollectionChangesIterator() as $changes) {
            $this->handleProductPriceJob($changes);
            $this->getManager()->remove($changes);
        }
        $this->getManager()->flush();
    }

    /**
     * @param ChangedProductPrice $changes
     */
    protected function handleProductPriceJob(ChangedProductPrice $changes)
    {
        $repository = $this->getCombinedPriceListRepository();
        $iterator = $repository->getCombinedPriceListsByPriceListProduct(
            $changes->getPriceList()
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
            $class = 'OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice';
            $this->manager = $this->registry->getManagerForClass($class);
        }

        return $this->manager;
    }

    /**
     * @return ChangedProductPriceRepository
     */
    protected function getQueueRepository()
    {
        if (!$this->queueRepository) {
            $class = 'OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice';
            $this->queueRepository = $this->getManager()->getRepository($class);
        }

        return $this->queueRepository;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRepository()
    {
        if (!$this->combinedPriceList) {
            $this->combinedPriceList = $this->registry
                ->getManagerForClass('OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList')
                ->getRepository('OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList');
        }

        return $this->combinedPriceList;
    }
}
