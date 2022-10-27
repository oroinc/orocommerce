<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
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
    /** @var PriceListTriggerHandler */
    private $priceListTriggerHandler;

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(
        PriceListTriggerHandler $priceListTriggerHandler,
        ManagerRegistry $doctrine
    ) {
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->doctrine = $doctrine;
    }

    /**
     * @param string   $className
     * @param array    $updatedFields
     * @param null|int $relationId
     *
     * @return PriceRuleLexeme[]
     */
    public function findEntityLexemes(string $className, array $updatedFields = [], int $relationId = null): array
    {
        /** @var PriceRuleLexemeRepository $repository */
        $repository = $this->doctrine->getRepository(PriceRuleLexeme::class);

        return $repository->findEntityLexemes($className, $updatedFields, $relationId);
    }

    /**
     * @param PriceRuleLexeme[] $lexemes
     * @param array|Product[]   $products
     */
    public function processLexemes(array $lexemes, array $products = []): void
    {
        if (!$lexemes) {
            return;
        }

        $assignmentsRecalculatePriceLists = [];
        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            if (!$lexeme->getPriceRule()) {
                $this->priceListTriggerHandler->handlePriceListTopic(
                    ResolvePriceListAssignedProductsTopic::getName(),
                    $priceList,
                    $products
                );
                $assignmentsRecalculatePriceLists[$priceList->getId()] = true;
            }
        }

        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            if ($lexeme->getPriceRule() && !array_key_exists($priceList->getId(), $assignmentsRecalculatePriceLists)) {
                $this->priceListTriggerHandler->handlePriceListTopic(
                    ResolvePriceRulesTopic::getName(),
                    $priceList,
                    $products
                );
            }
        }

        $this->markMentionedPriceListNotActual($lexemes);
    }

    /**
     * @param PriceRuleLexeme[] $lexemes
     */
    private function markMentionedPriceListNotActual(array $lexemes): void
    {
        $priceLists = [];
        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            $priceLists[$priceList->getId()] = $priceList;
        }

        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->doctrine->getRepository(PriceList::class);
        $priceListRepository->updatePriceListsActuality($priceLists, false);
    }
}
