<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;

class CombinedPriceListProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var CombinedProductPriceResolver
     */
    protected $resolver;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param array $priceListsRelations BasePriceListRelation[]
     * @return CombinedPriceList
     */
    public function getCombinedPriceList(array $priceListsRelations)
    {
        $normalizedCollection = $this->normalizeCollection($priceListsRelations);
        $identifier = $this->getCombinedPriceListIdentifier($normalizedCollection);
        $combinedPriceList = $this->registry->getManagerForClass($this->className)
            ->getRepository($this->className)->findBy(['name' => $identifier]);
        if (!$combinedPriceList) {
            $combinedPriceList = $this->createCombinedPriceList($normalizedCollection, $identifier);
            $this->resolver->combinePrices($combinedPriceList);
        }

        return $combinedPriceList;
    }


    /**
     * @param array $priceListsRelations BasePriceListRelation[]
     * @return string
     */
    protected function getCombinedPriceListIdentifier(array $priceListsRelations)
    {
        $key = '';
        /**
         * @var $priceListsRelations BasePriceListRelation[]
         */
        foreach ($priceListsRelations as $priceListRelation) {

            $isMergeAllowed = 'f';
            if ($priceListRelation->isMergeAllowed()) {
                $isMergeAllowed = 't';
            }
            $key .= $priceListRelation->getPriceList()->getId() . $isMergeAllowed;
        }

        return $key;
    }

    /**
     * @param array $priceListsRelations BasePriceListRelation[]
     * @return array BasePriceListRelation[]
     */
    protected function normalizeCollection(array $priceListsRelations)
    {
        $normalizedCollection = [];
        $usedPriceMap = [];
        /**
         * @var $priceListsRelations BasePriceListRelation[]
         */
        foreach ($priceListsRelations as $priceListsRelation) {
            $isDuplicate = false;
            $priceList = $priceListsRelation->getPriceList();
            if ($priceListsRelation->isMergeAllowed()) {
                if (isset($usedPriceMap[$priceList->getId()][$priceListsRelation->isMergeAllowed()])) {
                    $isDuplicate = true;
                }
            } else {
                if (isset($usedPriceMap[$priceList->getId()])) {
                    $isDuplicate = true;
                }
            }
            if ($isDuplicate) {
                continue;
            }
            $normalizedCollection[] = $priceListsRelation;
            $usedPriceMap[$priceList->getId()][$priceListsRelation->isMergeAllowed()] = true;
        }

        return $normalizedCollection;
    }

    /**
     * @param array $priceListsRelations BasePriceListRelation[]
     * @param string $identifier
     * @return CombinedPriceList
     */
    protected function createCombinedPriceList(array $priceListsRelations, $identifier)
    {
        /**
         * @var $priceListsRelations BasePriceListRelation[]
         */
        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList->setName($identifier);
        $combinedPriceList->setCurrencies($this->getCombineCurrencies($priceListsRelations));

        $manager = $this->registry->getManagerForClass($this->className);
        $manager->persist($combinedPriceList);
        $manager->flush();

        $i = 0;
        foreach ($priceListsRelations as $priceListsRelation) {
            $priceListToCombined = new CombinedPriceListToPriceList();
            $priceListToCombined->setMergeAllowed($priceListsRelation->isMergeAllowed());
            $priceListToCombined->setCombinedPriceList($combinedPriceList);
            $priceListToCombined->setPriceList($priceListsRelation->getPriceList());
            $priceListToCombined->setSortOrder($i++);
            $manager->persist($priceListToCombined);
        }
        $manager->flush();

        return $combinedPriceList;
    }

    /**
     * @param $priceListsRelations BasePriceListRelation[]
     * @return array
     */
    protected function getCombineCurrencies($priceListsRelations)
    {
        $currencies = [];
        /**
         * @var $priceListsRelations BasePriceListRelation[]
         */
        foreach ($priceListsRelations as $priceListsRelation) {
            $currencies = array_merge($currencies, $priceListsRelation->getPriceList()->getCurrencies());
        }
        $currencies = array_unique($currencies);

        return $currencies;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @param mixed $resolver
     */
    public function setResolver($resolver)
    {
        $this->resolver = $resolver;
    }
}
