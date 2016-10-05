<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PriceRuleLexemeTriggerHandler
{
    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param RegistryInterface $registry
     */
    public function __construct(
        PriceListTriggerHandler $priceListTriggerHandler,
        RegistryInterface $registry
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
     * @param Product|null $product
     */
    public function addTriggersByLexemes(array $lexemes, Product $product = null)
    {
        $assignmentsRecalculatePriceLists = [];
        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            if (!$lexeme->getPriceRule()) {
                $this->priceListTriggerHandler->addTriggerForPriceList(
                    Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
                    $priceList,
                    $product
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
                    $product
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
