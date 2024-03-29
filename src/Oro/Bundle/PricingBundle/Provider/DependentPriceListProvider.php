<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;

/**
 * This provider returns dependent price lists for given price list
 */
class DependentPriceListProvider
{
    /** @var PriceRuleLexemeTriggerHandler */
    protected $priceRuleLexemeTriggerHandler;

    public function __construct(PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler)
    {
        $this->priceRuleLexemeTriggerHandler = $priceRuleLexemeTriggerHandler;
    }

    /**
     * @param PriceList $priceList
     * @return array|PriceList[]
     */
    public function getDependentPriceLists(PriceList $priceList)
    {
        return $this->loadDependentPriceLists($priceList, false);
    }

    /**
     * @param PriceList $priceList
     * @return array|PriceList[]
     */
    public function getDirectlyDependentPriceLists(PriceList $priceList): array
    {
        return $this->loadDependentPriceLists($priceList, true);
    }

    private function loadDependentPriceLists(PriceList $priceList, bool $onlyDirect = false): array
    {
        $lexemes = $this->priceRuleLexemeTriggerHandler->findEntityLexemes(
            PriceList::class,
            [],
            $priceList->getId()
        );

        $dependentPriceLists = [];
        foreach ($lexemes as $lexeme) {
            $dependentPriceList = $lexeme->getPriceList();
            if ($dependentPriceList) {
                $dependentPriceLists[$dependentPriceList->getId()] = $dependentPriceList;
                if (!$onlyDirect) {
                    foreach ($this->getDependentPriceLists($dependentPriceList, false) as $subDependentPriceList) {
                        $dependentPriceLists[$subDependentPriceList->getId()] = $subDependentPriceList;
                    }
                }
            }
        }

        return $dependentPriceLists;
    }

    /**
     * @param iterable|PriceList[] $priceLists
     * @return PriceList[]
     */
    public function appendDependent($priceLists)
    {
        $priceListsWithDependent = [];
        foreach ($priceLists as $priceList) {
            $priceListsWithDependent[$priceList->getId()] = $priceList;
            foreach ($this->getDependentPriceLists($priceList) as $dependentPriceList) {
                $priceListsWithDependent[$dependentPriceList->getId()] = $dependentPriceList;
            }
        }

        return $priceListsWithDependent;
    }
}
