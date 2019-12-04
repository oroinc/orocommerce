<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Schedule recalculations related to product price rules with lexemes.
 */
class PriceRuleLexemeTriggerHandler
{
    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param ManagerRegistry $registry
     */
    public function __construct(
        PriceListTriggerHandler $priceListTriggerHandler,
        ManagerRegistry $registry
    ) {
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->registry = $registry;
    }

    /**
     * @param string $className
     * @param array $updatedFields
     * @param null|int $relationId
     * @return array|PriceRuleLexeme[]
     */
    public function findEntityLexemes($className, array $updatedFields = [], $relationId = null)
    {
        /** @var PriceRuleLexemeRepository $repository */
        $repository = $this->registry
            ->getManagerForClass(PriceRuleLexeme::class)
            ->getRepository(PriceRuleLexeme::class);

        return $repository->findEntityLexemes($className, $updatedFields, $relationId);
    }

    /**
     * @param PriceRuleLexeme[] $lexemes
     * @param array|Product[] $products
     */
    public function addTriggersByLexemes(array $lexemes, array $products = [])
    {
        if (!$lexemes) {
            return;
        }

        $assignmentsRecalculatePriceLists = [];
        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            if (!$lexeme->getPriceRule()) {
                $this->priceListTriggerHandler->addTriggerForPriceList(
                    Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
                    $priceList,
                    $products
                );
                $assignmentsRecalculatePriceLists[$priceList->getId()] = true;
            }
        }

        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            if ($lexeme->getPriceRule() && !array_key_exists($priceList->getId(), $assignmentsRecalculatePriceLists)) {
                $this->priceListTriggerHandler->addTriggerForPriceList(
                    Topics::RESOLVE_PRICE_RULES,
                    $priceList,
                    $products
                );
            }
        }

        $this->markMentionedPriceListNotActual($lexemes);
    }

    /**
     * @param array|PriceRuleLexeme[] $lexemes
     */
    protected function markMentionedPriceListNotActual(array $lexemes)
    {
        $priceLists = [];
        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            $priceLists[$priceList->getId()] = $priceList;
        }

        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->registry
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);
        $priceListRepository->updatePriceListsActuality($priceLists, false);
    }
}
