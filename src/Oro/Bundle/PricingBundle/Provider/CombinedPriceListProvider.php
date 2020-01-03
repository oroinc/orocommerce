<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListCreateEvent;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provide and actualize combined price list by given price list sequence members.
 */
class CombinedPriceListProvider
{
    const GLUE = CombinedPriceListIdentifierProviderInterface::GLUE;
    const MERGE_NOT_ALLOWED_FLAG = 'f';
    const MERGE_ALLOWED_FLAG = 't';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var StrategyRegister
     */
    protected $strategyRegister;

    /**
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param StrategyRegister $strategyRegister
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        StrategyRegister $strategyRegister
    ) {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->strategyRegister = $strategyRegister;
    }

    /**
     * @param PriceListSequenceMember[] $priceListsRelations
     * @param array $eventOptions
     * @return CombinedPriceList
     */
    public function getCombinedPriceList(array $priceListsRelations, array $eventOptions = [])
    {
        $normalizedCollection = $this->normalizeCollection($priceListsRelations);
        $identifier = $this->getCombinedPriceListIdentifier($normalizedCollection);
        $combinedPriceList = $this->getRepository()->findOneBy(['name' => $identifier]);

        if (!$combinedPriceList) {
            $combinedPriceList = $this->createCombinedPriceList($identifier);
            $this->updateCombinedPriceList($combinedPriceList, $normalizedCollection);

            $this->eventDispatcher->dispatch(
                CombinedPriceListCreateEvent::NAME,
                new CombinedPriceListCreateEvent($combinedPriceList, $eventOptions)
            );
        }

        return $combinedPriceList;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|PriceListSequenceMember[] $priceListsRelations
     */
    public function actualizeCurrencies(CombinedPriceList $combinedPriceList, array $priceListsRelations)
    {
        $combinedPriceList->setCurrencies($this->getCombinedCurrenciesList($priceListsRelations));
    }

    /**
     * @param PriceListSequenceMember[] $priceListsRelations
     * @return string
     */
    protected function getCombinedPriceListIdentifier(array $priceListsRelations)
    {
        $strategy = $this->strategyRegister->getCurrentStrategy();
        if ($strategy instanceof CombinedPriceListIdentifierProviderInterface) {
            return $strategy->getCombinedPriceListIdentifier($priceListsRelations);
        }

        return $this->getDefaultCombinedPriceListIdentifier($priceListsRelations);
    }

    /**
     * @param array $priceListsRelations
     * @return string
     */
    private function getDefaultCombinedPriceListIdentifier(array $priceListsRelations): string
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

        return $combinedPriceList;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param PriceListSequenceMember[] $priceListsRelations
     */
    protected function updateCombinedPriceList(CombinedPriceList $combinedPriceList, array $priceListsRelations)
    {
        $manager = $this->getManager();
        $this->actualizeCurrencies($combinedPriceList, $priceListsRelations);
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
            $currencies[] = $priceListsRelation->getPriceList()->getCurrencies();
        }

        if ($currencies) {
            $currencies = array_merge(...$currencies);
        }

        return array_unique($currencies);
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->registry->getManagerForClass(CombinedPriceList::class);
        }

        return $this->manager;
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->getManager()->getRepository(CombinedPriceList::class);
        }

        return $this->repository;
    }
}
