<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;

class CombinedPriceListProvider
{
    const GLUE = '_';
    const MERGE_NOT_ALLOWED_FLAG = 'f';
    const MERGE_ALLOWED_FLAG = 't';

    const BEHAVIOR_DEFAULT = 1;
    const BEHAVIOR_FORCE = 2;
    const BEHAVIOR_EMPTY = 3;

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
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * @param CombinedProductPriceResolver $resolver
     */
    public function setResolver(CombinedProductPriceResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param PriceListSequenceMember[] $priceListsRelations
     * @param int|null $behavior
     * @return CombinedPriceList
     */
    public function getCombinedPriceList(array $priceListsRelations, $behavior = null)
    {
        if ($behavior === null) {
            $behavior = self::BEHAVIOR_DEFAULT;
        }
        $normalizedCollection = $this->normalizeCollection($priceListsRelations);
        $identifier = $this->getCombinedPriceListIdentifier($normalizedCollection);
        $combinedPriceList = $this->getRepository()->findOneBy(['name' => $identifier]);

        if (!$combinedPriceList || $behavior == self::BEHAVIOR_FORCE) {
            if (!$combinedPriceList) {
                $combinedPriceList = $this->createCombinedPriceList($identifier);
            }
            $this->updateCombinedPriceList($combinedPriceList, $normalizedCollection);

            //TODO: Move this to one level UP
            if ($behavior !== self::BEHAVIOR_EMPTY) {
                $this->resolver->combinePrices($combinedPriceList);
            }
        }

        return $combinedPriceList;
    }

    /**
     * @param PriceListSequenceMember[] $priceListsRelations
     * @return string
     */
    protected function getCombinedPriceListIdentifier(array $priceListsRelations)
    {
        $key = [];
        foreach ($priceListsRelations as $priceListSequenceMember) {
            $isMergeAllowed = self::MERGE_NOT_ALLOWED_FLAG;
            if ($priceListSequenceMember->isMergeAllowed()) {
                $isMergeAllowed = self::MERGE_ALLOWED_FLAG;
            }
            $key[] = $priceListSequenceMember->getPriceList()->getId() . $isMergeAllowed;
        }

        return md5(implode(self::GLUE, $key));
    }

    /**
     * @param PriceListSequenceMember[] $priceListsRelations
     * @return array PriceListSequenceMember[]
     */
    protected function normalizeCollection(array $priceListsRelations)
    {
        $normalizedCollection = [];
        $usedPriceMap = [];
        foreach ($priceListsRelations as $priceListsRelation) {
            $priceListId = $priceListsRelation->getPriceList()->getId();
            $isMergeAllowed = $priceListsRelation->isMergeAllowed();
            if (($isMergeAllowed && isset($usedPriceMap[$priceListId][$isMergeAllowed]))
                || (!$isMergeAllowed && isset($usedPriceMap[$priceListId]))
            ) {
                continue;
            }

            $normalizedCollection[] = $priceListsRelation;
            $usedPriceMap[$priceListId][$isMergeAllowed] = true;
        }

        return $normalizedCollection;
    }

    /**
     * @param string $identifier
     * @return CombinedPriceList
     */
    protected function createCombinedPriceList($identifier)
    {
        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList->setName($identifier);
        $combinedPriceList->setEnabled(true);

        $manager = $this->getManager();
        $manager->persist($combinedPriceList);

        //TODO: build activation plan here with CombinedPriceListActivationPlanBuilder

        return $combinedPriceList;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param PriceListSequenceMember[] $priceListsRelations
     */
    protected function updateCombinedPriceList(CombinedPriceList $combinedPriceList, array $priceListsRelations)
    {
        $manager = $this->getManager();
        $combinedPriceList->setCurrencies($this->getCombinedCurrenciesList($priceListsRelations));
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
    }

    /**
     * @param PriceListSequenceMember[] $priceListsRelations
     * @return array
     */
    protected function getCombinedCurrenciesList($priceListsRelations)
    {
        $currencies = [];
        foreach ($priceListsRelations as $priceListsRelation) {
            $currencies = array_merge($currencies, $priceListsRelation->getPriceList()->getCurrencies());
        }
        $currencies = array_unique($currencies);

        return $currencies;
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->registry->getManagerForClass($this->className);
        }

        return $this->manager;
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->getManager()->getRepository($this->className);
        }

        return $this->repository;
    }
}
