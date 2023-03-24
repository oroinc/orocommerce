<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
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

    protected ManagerRegistry $registry;
    protected EventDispatcherInterface $eventDispatcher;
    protected StrategyRegister $strategyRegister;
    protected ShardManager $shardManager;
    private array $cpls = [];

    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        StrategyRegister $strategyRegister,
        ShardManager $shardManager
    ) {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->strategyRegister = $strategyRegister;
        $this->shardManager = $shardManager;
    }

    /**
     * @param PriceListSequenceMember[] $priceListsRelations
     * @param array $eventOptions
     *
     * @return CombinedPriceList
     */
    public function getCombinedPriceList(array $priceListsRelations, array $eventOptions = []): CombinedPriceList
    {
        $normalizedCollection = $this->normalizeCollection($priceListsRelations);
        $identifier = $this->getCombinedPriceListIdentifier($normalizedCollection);

        if (!array_key_exists($identifier, $this->cpls)) {
            $combinedPriceList = $this->getCombinedPriceListRepository()->findOneBy(['name' => $identifier]);
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

    public function actualizeCurrencies(CombinedPriceList $combinedPriceList, array $priceListsRelations): void
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
        return $this->getCombinedPriceListRepository()->find($id);
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

    private function getCombinedPriceListIdentifier(array $priceListsRelations): string
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

    private function normalizeCollection(array $priceListsRelations): array
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
        return array_filter($relations, function ($relation) {
            /** @var PriceList $priceList */
            $priceList = $relation->getPriceList();

            return
                $priceList->isActive()
                && $this->getProductPriceRepository()->hasPrices($this->shardManager, $priceList);
        });
    }

    private function createCombinedPriceList($identifier): CombinedPriceList
    {
        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList->setName($identifier);
        $combinedPriceList->setEnabled(true);

        $manager = $this->registry->getManagerForClass(CombinedPriceList::class);
        $manager->persist($combinedPriceList);

        return $combinedPriceList;
    }

    private function updateCombinedPriceList(CombinedPriceList $combinedPriceList, array $priceListsRelations): void
    {
        $manager = $this->registry->getManagerForClass(CombinedPriceList::class);
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

    private function getCombinedCurrenciesList(array $priceListsRelations): array
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

    private function getCombinedPriceListRepository(): CombinedPriceListRepository
    {
        return $this->registry->getRepository(CombinedPriceList::class);
    }

    private function getProductPriceRepository(): ProductPriceRepository
    {
        return $this->registry->getRepository(ProductPrice::class);
    }
}
