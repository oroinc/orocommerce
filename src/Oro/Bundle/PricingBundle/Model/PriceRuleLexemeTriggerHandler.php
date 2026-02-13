<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
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
    public function __construct(
        private PriceListTriggerHandler $priceListTriggerHandler,
        private ManagerRegistry $doctrine
    ) {
    }

    /**
     * @param string $className
     * @param array|string[] $updatedFields
     * @param int|null $relationId
     * @param Organization|null $organization
     * @return array|PriceRuleLexeme[]
     */
    public function findEntityLexemes(
        string $className,
        array $updatedFields = [],
        ?int $relationId = null,
        ?Organization $organization = null
    ): array {
        /** @var PriceRuleLexemeRepository $repository */
        $repository = $this->doctrine->getRepository(PriceRuleLexeme::class);

        return $repository->findEntityLexemes($className, $updatedFields, $relationId, $organization);
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

        $this->markMentionedPriceListNotActual($lexemes, $products);

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
    }

    private function isPriceListShouldBeProcessed(PriceList $priceList, array $products): bool
    {
        if (!$products) {
            return true;
        }

        $priceListOrganizationId = $priceList->getOrganization()?->getId();
        foreach ($products as $product) {
            if (
                null === $product
                || (\is_object($product) && $priceListOrganizationId !== $product->getOrganization()->getId())
            ) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param PriceRuleLexeme[] $lexemes
     * @param array|Product[] $products
     */
    private function markMentionedPriceListNotActual(array $lexemes, array $products): void
    {
        $priceLists = [];
        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            if (!$priceList) {
                continue;
            }

            if ($this->isPriceListShouldBeProcessed($priceList, $products)) {
                $priceLists[$priceList->getId()] = $priceList;
            }
        }

        if ($priceLists) {
            /** @var PriceListRepository $priceListRepository */
            $priceListRepository = $this->doctrine->getRepository(PriceList::class);
            $priceListRepository->updatePriceListsActuality($priceLists, false);
        }
    }
}
