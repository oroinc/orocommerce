<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AffectedPriceListsHandler
{
    const FIELD_PRODUCT_ASSIGNMENT_RULES = 'productAssignmentRule';
    const FIELD_ASSIGNED_PRODUCTS = 'assignedProducts';

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var PriceListTreeHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @param RegistryInterface $registry
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param Cache $cache
     */
    public function __construct(
        RegistryInterface $registry,
        PriceListTriggerHandler $priceListTriggerHandler,
        Cache $cache
    ) {
        $this->registry = $registry;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->cache = $cache;
    }

    /**
     * @param PriceList $priceList
     * @param string $fieldName
     * @param bool $isPriceRuleLexeme
     */
    public function recalculateByPriceList(PriceList $priceList, $fieldName, $isPriceRuleLexeme)
    {
        $affectedPriceListsLexemes = $this->getAffectedPriceListsLexemes($priceList, $fieldName, $isPriceRuleLexeme);

        $this->addTriggersByLexemes($affectedPriceListsLexemes);
    }

    /**
     * @param PriceList $priceList
     * @param string $fieldName
     * @param string $isPriceRuleLexeme
     * @return array
     */
    protected function getAffectedPriceListsLexemes(PriceList $priceList, $fieldName, $isPriceRuleLexeme)
    {
        $lexemes = $this->registry->getManagerForClass(PriceRuleLexeme::class)
            ->getRepository(PriceRuleLexeme::class)
            ->getAffectedPriceRuleLexemes($priceList, $fieldName, $isPriceRuleLexeme);

        foreach ($lexemes as $lexeme) {
            $lexemes = array_merge(
                $lexemes,
                $this->getAffectedPriceListsLexemes($lexeme->getPriceList(), $fieldName, $isPriceRuleLexeme)
            );
        }

        return $lexemes;
    }

    /**
     * @param PriceRuleLexeme[] $lexemes
     */
    protected function addTriggersByLexemes(array $lexemes)
    {
        $priceLists = [];

        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            $priceLists[$priceList->getId()] = $priceList;

            $this->clearAssignmentRuleCache($priceList);
            $this->clearPriceRuleCache($priceList);
        }

        $this->priceListTriggerHandler->addTriggersForPriceLists(Topics::CALCULATE_RULE, $priceLists);
        $this->updatePriceListActuality($priceLists, false);
    }

    /**
     * @param array|PriceList[] $priceLists
     * @param bool $isActual
     */
    protected function updatePriceListActuality(array $priceLists, $isActual)
    {
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->registry->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);
        $priceListRepository->updatePriceListsActuality($priceLists, $isActual);
    }

    /**
     * @param PriceList $priceList
     */
    protected function clearAssignmentRuleCache(PriceList $priceList)
    {
        $this->cache->delete('ar_' . $priceList->getId());
    }

    /**
     * @param PriceList $priceList
     */
    protected function clearPriceRuleCache(PriceList $priceList)
    {
        $this->cache->delete('pr_' . $priceList->getId());
    }
}
