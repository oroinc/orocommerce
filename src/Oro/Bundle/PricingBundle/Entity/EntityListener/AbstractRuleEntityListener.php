<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Expression\FieldsProviderInterface;

/**
 * The base class for listeners which watch product changes and execute price recalculation.
 */
abstract class AbstractRuleEntityListener
{
    /** @var PriceRuleLexemeTriggerHandler */
    protected $priceRuleLexemeTriggerHandler;

    /** @var FieldsProviderInterface */
    protected $fieldsProvider;

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(
        PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler,
        FieldsProviderInterface $fieldsProvider,
        ManagerRegistry $registry
    ) {
        $this->priceRuleLexemeTriggerHandler = $priceRuleLexemeTriggerHandler;
        $this->fieldsProvider = $fieldsProvider;
        $this->registry = $registry;
    }

    /**
     * @return string
     */
    abstract protected function getEntityClassName();

    /**
     * @param array $changeSet
     * @param Product $product
     * @param int|null $relationId
     */
    protected function recalculateByEntityFieldsUpdate(array $changeSet, Product $product = null, $relationId = null)
    {
        $fields = $this->getEntityFields();
        $updatedFields = array_intersect($fields, array_keys($changeSet));

        if ($updatedFields) {
            $lexemes = $this->priceRuleLexemeTriggerHandler->findEntityLexemes(
                $this->getEntityClassName(),
                $updatedFields,
                $relationId
            );
            $this->priceRuleLexemeTriggerHandler->processLexemes($lexemes, [$product]);
        }
    }

    /**
     * @param Product|null $product
     * @param int|null $relationId
     */
    protected function recalculateByEntity(Product $product = null, $relationId = null)
    {
        $lexemes = $this->priceRuleLexemeTriggerHandler->findEntityLexemes(
            $this->getEntityClassName(),
            [],
            $relationId
        );
        $this->priceRuleLexemeTriggerHandler->processLexemes($lexemes, $product ? [$product] : []);
    }

    /**
     * @return array
     */
    protected function getEntityFields()
    {
        return $this->fieldsProvider->getFields($this->getEntityClassName(), false, true);
    }
}
