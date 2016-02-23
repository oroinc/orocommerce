<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;

abstract class AbstractCombinedPriceListBuilder
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    protected $garbageCollector;

    /**
     * @var PriceListCollectionProvider
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider
     */
    protected $combinedPriceListProvider;

    /**
     * @var EntityRepository
     */
    protected $priceListToEntityRepository;

    /**
     * @var string
     */
    protected $priceListToEntityClassName;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListRepository;

    /**
     * @var EntityRepository
     */
    protected $combinedPriceListToEntityRepository;

    /**
     * @var string
     */
    protected $combinedPriceListClassName;

    /**
     * @var string
     */
    protected $combinedPriceListToEntityClassName;

    /**
     * @var array
     */
    protected $builtList;

    /**
     * @param ManagerRegistry $registry
     * @param PriceListCollectionProvider $priceListCollectionProvider
     * @param CombinedPriceListProvider $combinedPriceListProvider
     * @param CombinedPriceListGarbageCollector $garbageCollector
     */
    public function __construct(
        ManagerRegistry $registry,
        PriceListCollectionProvider $priceListCollectionProvider,
        CombinedPriceListProvider $combinedPriceListProvider,
        CombinedPriceListGarbageCollector $garbageCollector
    ) {
        $this->registry = $registry;
        $this->priceListCollectionProvider = $priceListCollectionProvider;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
        $this->garbageCollector = $garbageCollector;
    }

    /**
     * @param string $priceListToEntityClassName
     * @return $this
     */
    public function setPriceListToEntityClassName($priceListToEntityClassName)
    {
        $this->priceListToEntityClassName = $priceListToEntityClassName;

        return $this;
    }

    /**
     * @param string $combinedPriceListClassName
     * @return $this
     */
    public function setCombinedPriceListClassName($combinedPriceListClassName)
    {
        $this->combinedPriceListClassName = $combinedPriceListClassName;

        return $this;
    }

    /**
     * @return EntityRepository
     */
    protected function getPriceListToEntityRepository()
    {
        if (!$this->priceListToEntityRepository) {
            $this->priceListToEntityRepository = $this->registry
                ->getManagerForClass($this->priceListToEntityClassName)
                ->getRepository($this->priceListToEntityClassName);
        }

        return $this->priceListToEntityRepository;
    }

    /**
     * @return EntityRepository
     */
    protected function getCombinedPriceListToEntityRepository()
    {
        if (!$this->combinedPriceListToEntityRepository) {
            $this->combinedPriceListToEntityRepository = $this->registry
                ->getManagerForClass($this->combinedPriceListToEntityClassName)
                ->getRepository($this->combinedPriceListToEntityClassName);
        }

        return $this->combinedPriceListToEntityRepository;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRepository()
    {
        if (!$this->combinedPriceListRepository) {
            $this->combinedPriceListRepository = $this->registry
                ->getManagerForClass($this->combinedPriceListClassName)
                ->getRepository($this->combinedPriceListClassName);
        }

        return $this->combinedPriceListRepository;
    }

    /**
     * @return string
     */
    public function getCombinedPriceListToEntityClassName()
    {
        return $this->combinedPriceListToEntityClassName;
    }

    /**
     * @param string $combinedPriceListToEntityClassName
     */
    public function setCombinedPriceListToEntityClassName($combinedPriceListToEntityClassName)
    {
        $this->combinedPriceListToEntityClassName = $combinedPriceListToEntityClassName;
    }
}
