<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListCreateEvent;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
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
     * @var ProductPriceRepository
     */
    protected $productPriceRepository;

    /**
     * @var StrategyRegister
     */
    protected $strategyRegister;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var array|CombinedPriceList[]
     */
    protected $cpls = [];

    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        StrategyRegister $strategyRegister
    ) {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->strategyRegister = $strategyRegister;
    }

    public function setShardManager(ShardManager $shardManager): void
    {
        $this->shardManager = $shardManager;
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

        if (!array_key_exists($identifier, $this->cpls)) {
            $combinedPriceList = $this->getRepository()->findOneBy(['name' => $identifier]);
        } else {
            return $this->cpls[$identifier];
        }

        if (!$combinedPriceList) {
            $combinedPriceList = $this->createCombinedPriceList($identifier);
            $this->updateCombinedPriceList($combinedPriceList, $normalizedCollection);

            $this->eventDispatcher->dispatch(
                new CombinedPriceListCreateEvent($combinedPriceList, $eventOptions),
                CombinedPriceListCreateEvent::NAME
            );
        }
        $this->cpls[$identifier] = $combinedPriceList;

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

    public function getCollectionInformation(array $priceListsRelations): array
    {
        $collectionElements = array_map(static function (PriceListSequenceMember $member) {
            return ['p' => $member->getPriceList()->getId(), 'm' => $member->isMergeAllowed()];
        }, $priceListsRelations);

        return [
            'identifier' => $this->getCombinedPriceListIdentifier($priceListsRelations),
            'elements' => $collectionElements
        ];
    }

    public function getCombinedPriceListById(int $id): ?CombinedPriceList
    {
        return $this->getRepository()->find($id);
    }

    public function getCombinedPriceListByCollectionInformation(array $collectionInfo): CombinedPriceList
    {
        $repo = $this->registry->getRepository(PriceList::class);
        $sequenceMembers = [];

        if (count($collectionInfo)) {
            $priceLists = [];
            foreach ($repo->findBy(['id' => array_column($collectionInfo, 'p')]) as $priceList) {
                $priceLists[$priceList->getId()] = $priceList;
            }

            $sequenceMembers = array_map(static function (array $member) use ($priceLists) {
                $id = $member['p'];
                if (!array_key_exists($id, $priceLists)) {
                    throw EntityNotFoundException::fromClassNameAndIdentifier(PriceList::class, ['id' => $id]);
                }

                return new PriceListSequenceMember(
                    $priceLists[$id],
                    (bool)$member['m']
                );
            }, $collectionInfo);
        }

        return $this->getCombinedPriceList($sequenceMembers);
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

        return $this->filterPriceListRelations($normalizedCollection);
    }

    private function filterPriceListRelations(array $relations): array
    {
        $filteredRelations = array_filter($relations, function ($relation) {
            /** @var PriceList $priceList */
            $priceList = $relation->getPriceList();

            return
                $priceList->isActive()
                && $this->getProductPriceRepository()->hasPrices($this->shardManager, $priceList);
        });

        // At least one combined price list must be in the application for all available products.
        if (count($filteredRelations) === 0) {
            $filteredRelations = array_filter($relations, fn ($relation) => $relation->getPriceList()->isDefault());
        }

        return $filteredRelations;
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

        $entities = [];
        if (!$combinedPriceList->getId()) {
            $entities[] = $combinedPriceList;
        }
        foreach ($priceListsRelations as $priceListsRelation) {
            $priceListToCombined = new CombinedPriceListToPriceList();
            $priceListToCombined->setMergeAllowed($priceListsRelation->isMergeAllowed());
            $priceListToCombined->setCombinedPriceList($combinedPriceList);
            $priceListToCombined->setPriceList($priceListsRelation->getPriceList());
            $priceListToCombined->setSortOrder($i++);
            $manager->persist($priceListToCombined);
            $entities[] = $priceListToCombined;
        }
        $manager->flush($entities);
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
        return $this->registry->getRepository(CombinedPriceList::class);
    }

    private function getProductPriceRepository(): ProductPriceRepository
    {
        return $this->registry->getRepository(ProductPrice::class);
    }
}
