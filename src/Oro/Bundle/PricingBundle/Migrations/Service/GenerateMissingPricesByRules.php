<?php

namespace Oro\Bundle\PricingBundle\Migrations\Service;

use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Generates missing product prices for dependent price lists.
 *
 *  - if sharding is enabled prices generation is triggered for all ProductPrice based lexemes
 *  - if productAssignmentRule depends on generated Price List such rule is triggered for all products
 *  - if priceRule depends on generated Price List such rule is triggered for all products
 *  - if there are missing products that has to be assigned by productAssignmentRule,
 *    then productAssignmentRule is recalculated only for these products
 *  - if there are products that has no prices but has to have by priceRule,
 *     then priceRule is recalculated only for these products
 */
class GenerateMissingPricesByRules
{
    public function __construct(
        private PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler,
        private ProductAssignmentRuleCompiler $assignmentRuleCompiler,
        private PriceListRuleCompiler $ruleCompiler,
        private ShardManager $shardManager
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function migrate(): void
    {
        $lexemes = $this->priceRuleLexemeTriggerHandler->findEntityLexemes(ProductPrice::class);

        // When sharding is enabled recalculate all without a try to find broken products,
        // because queries will be unpredictable more complex as prices may be stored in different tables.
        if ($this->shardManager->isShardingEnabled()) {
            $this->priceRuleLexemeTriggerHandler->processLexemes($lexemes);

            return;
        }

        $affectedPriceLists = [];
        $assignmentLexemes = [];
        $ruleLexemes = [];

        foreach ($lexemes as $lexeme) {
            $priceListId = $lexeme->getPriceList()->getId();
            if ($lexeme->getPriceRule()) {
                $ruleLexemes[$priceListId][] = $lexeme;
            } else {
                $assignmentLexemes[$priceListId][] = $lexeme;
            }
            $affectedPriceLists[$priceListId] = true;
        }
        unset($lexemes);

        $priceListWithAssignmentRules = $this->getPriceListIdsWithAssignmentRules();
        $priceListWithPriceRules = $this->getPriceListIdsWithPriceRules();

        foreach (array_keys($affectedPriceLists) as $affectedPriceListId) {
            // Process assignment lexemes first, because if there is an assignment lexeme - then rule lexeme
            // for the same set of products has to be skipped,
            // because assignment processing will trigger rule processing
            $assignmentLexemes = $assignmentLexemes[$affectedPriceListId] ?? [];
            $notAssignedProductIds = [];
            $priceListWithAssignmentRule = null;
            foreach ($assignmentLexemes as $assignmentLexeme) {
                $priceListWithAssignmentRule = $assignmentLexeme->getPriceList();

                // If assignment rule is based on generated price list - schedule full refresh.
                if (\in_array($assignmentLexeme->getRelationId(), $priceListWithAssignmentRules)) {
                    $this->priceRuleLexemeTriggerHandler->processLexemes([$assignmentLexeme]);
                    continue 2;
                }
            }

            // If there is an assignment rule for affected price list - get list of affected product ids.
            if ($priceListWithAssignmentRule) {
                $notAssignedProductIds = $this->getAffectedAssignedProductIds($priceListWithAssignmentRule);

                if ($notAssignedProductIds) {
                    $this->priceRuleLexemeTriggerHandler->processLexemes(
                        [reset($assignmentLexemes)],
                        $notAssignedProductIds
                    );
                }
            }

            // Process price rule lexemes
            $productsAffectedByRules = [];
            $affectedRuleLexeme = null;
            $processedRules = [];
            foreach ($ruleLexemes[$affectedPriceListId] ?? [] as $ruleLexeme) {
                // If calculation rule is based on generated price list - schedule full rule recalculation.
                if (\in_array($ruleLexeme->getRelationId(), $priceListWithPriceRules)) {
                    $this->priceRuleLexemeTriggerHandler->processLexemes([$ruleLexeme]);
                    continue 2;
                }

                if (!empty($processedRules[$ruleLexeme->getPriceRule()->getId()])) {
                    continue;
                }

                $ruleAffectedProductIds = $this->getAffectedRuleProductIds(
                    $ruleLexeme->getPriceRule(),
                    $notAssignedProductIds
                );
                if ($ruleAffectedProductIds) {
                    $productsAffectedByRules[] = $ruleAffectedProductIds;
                    $affectedRuleLexeme = $ruleLexeme;
                }
                $processedRules[$ruleLexeme->getPriceRule()->getId()] = true;
            }

            if ($productsAffectedByRules) {
                $this->priceRuleLexemeTriggerHandler->processLexemes(
                    [$affectedRuleLexeme],
                    array_merge(...$productsAffectedByRules)
                );
            }
        }
    }

    /**
     * Returns a list of product ids that should be assigned by productAssignmentRule, but the assignment is missing.
     */
    private function getAffectedAssignedProductIds(PriceList $priceList): array
    {
        $qb = $this->assignmentRuleCompiler->compileQueryBuilder($priceList);
        $rootAlias = $qb->getRootAliases()[0];

        $em = $qb->getEntityManager();
        $assignedProductsQb = $em->createQueryBuilder();
        $assignedProductsQb->select('a.id')
            ->from(PriceListToProduct::class, 'a')
            ->where($assignedProductsQb->expr()->eq('a.priceList', ':migrationPriceList'))
            ->andWhere($assignedProductsQb->expr()->eq('a.product', $rootAlias));

        $qb->resetDQLPart('select')
            ->select($rootAlias . '.id');

        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists($assignedProductsQb->getDQL())
            )
        );
        $qb->setParameter('migrationPriceList', $priceList);

        return $qb->getQuery()->getSingleColumnResult();
    }

    /**
     * Returns a list of product ids that were not requested for reassembly by the assignmentRule and that have prices,
     * but are included in the priceRule.
     */
    private function getAffectedRuleProductIds(PriceRule $priceRule, array $notAssignedProductIds): array
    {
        $qb = $this->ruleCompiler->compileQueryBuilder($priceRule);
        $rootAlias = $qb->getRootAliases()[0];
        $qb->resetDQLPart('select')
            ->select($rootAlias . '.id')
            ->distinct(true);

        if ($notAssignedProductIds) {
            $qb->andWhere(
                $qb->expr()->notIn($rootAlias . '.id', ':skippedProductIds')
            );
            $qb->setParameter('skippedProductIds', $notAssignedProductIds);
        }

        return $qb->getQuery()->getSingleColumnResult();
    }

    private function getPriceListIdsWithAssignmentRules(): array
    {
        $em = $this->shardManager->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('pl.id')
            ->from(PriceList::class, 'pl')
            ->where($qb->expr()->isNotNull('pl.productAssignmentRule'));

        return $qb->getQuery()->getSingleColumnResult();
    }

    private function getPriceListIdsWithPriceRules(): array
    {
        $em = $this->shardManager->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('IDENTITY(l.priceList) as plId')
            ->distinct(true)
            ->from(PriceRuleLexeme::class, 'l')
            ->where($qb->expr()->isNotNull('l.priceRule'));

        return $qb->getQuery()->getSingleColumnResult();
    }
}
