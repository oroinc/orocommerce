<?php

namespace Oro\Bundle\PricingBundle\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\RelationNode;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PriceListRuleCompiler extends AbstractRuleCompiler
{
    /**
     * @var array
     */
    protected static $fieldsOrder = [
        'product',
        'priceList',
        'unit',
        'currency',
        'quantity',
        'productSku',
        'priceRule',
        'value',
    ];

    /**
     * @var array
     */
    protected $requiredPriceConditions = [
        'currency' => true,
        'quantity' => true,
        'unit' => true,
    ];

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldsProvider;

    /**
     * @var array
     */
    protected $usedPriceRelations = [];

    /**
     * @var array
     */
    protected $qbSelectPart = [];

    /**
     * @param PriceRuleFieldsProvider $fieldsProvider
     */
    public function setFieldsProvider(PriceRuleFieldsProvider $fieldsProvider)
    {
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * @param PriceRule $rule
     * @param Product $product
     * @return QueryBuilder
     */
    public function compile(PriceRule $rule, Product $product = null)
    {
        $cacheKey = 'pr_' . $rule->getId();
        $qb = $this->cache->fetch($cacheKey);
        if (!$qb) {
            $this->reset();

            $qb = $this->createQueryBuilder($rule);
            $rootAlias = $this->getRootAlias($qb);

            $this->modifySelectPart($qb, $rule, $rootAlias);
            $this->applyRuleConditions($qb, $rule);
            $this->restrictBySupportedUnits($qb, $rule, $rootAlias);
            $this->restrictBySupportedCurrencies($qb, $rule);
            $this->restrictBySupportedQuantity($qb, $rule);
            $this->restrictByAssignedProducts($rule, $qb, $rootAlias);
            $this->restrictByManualPrices($qb, $rule, $rootAlias);

            $this->cache->save($cacheKey, $qb);
        }

        $this->restrictByGivenProduct($qb, $product);

        return $qb;
    }

    protected function reset()
    {
        $this->usedPriceRelations = [];
    }

    /**
     * @param PriceRule $rule
     * @return QueryBuilder
     */
    protected function createQueryBuilder(PriceRule $rule)
    {
        $ruleCondition = $this->getProcessedRuleCondition($rule);
        if ($ruleCondition) {
            $expression = sprintf('%s and (%s) > 0', $ruleCondition, $rule->getRule());
        } else {
            $expression = $rule->getRule();
        }
        if ($rule->getCurrencyExpression()) {
            $expression .= sprintf(' and %s != null', $rule->getCurrencyExpression());
        }
        if ($rule->getQuantityExpression()) {
            $expression .= sprintf(' and %s != null', $rule->getQuantityExpression());
        }
        if ($rule->getProductUnitExpression()) {
            $expression .= sprintf(' and %s != null', $rule->getProductUnitExpression());
        }

        $node = $this->expressionParser->parse($expression);
        $this->saveUsedPriceRelations($node);
        $source = $this->nodeConverter->convert($node);

        return $this->queryConverter->convert($source);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrderedFields()
    {
        return self::$fieldsOrder;
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     * @param string $rootAlias
     */
    protected function modifySelectPart(QueryBuilder $qb, PriceRule $rule, $rootAlias)
    {
        $params = [];
        $priceValue = (string)$this->getValueByExpression($qb, $rule->getRule(), $params);

        if ($rule->getCurrencyExpression()) {
            $currencyValue = (string)$this->getValueByExpression($qb, $rule->getCurrencyExpression(), $params);
        } else {
            $currencyValue = (string)$qb->expr()->literal($rule->getCurrency());
        }

        if ($rule->getQuantityExpression()) {
            $quantityValue = (string)$this->getValueByExpression($qb, $rule->getQuantityExpression(), $params);
        } else {
            $quantityValue = (string)$qb->expr()->literal($rule->getQuantity());
        }

        if ($rule->getProductUnitExpression()) {
            $unitValue = sprintf(
                'IDENTITY(%s)',
                (string)$this->getValueByExpression(
                    $qb,
                    $rule->getProductUnitExpression(),
                    $params
                )
            );
        } else {
            $unitValue = (string)$qb->expr()->literal($rule->getProductUnit()->getCode());
        }

        $this->qbSelectPart = [
            'product' => $rootAlias.'.id',
            'productSku' => $rootAlias.'.sku',
            'priceList' => (string)$qb->expr()->literal($rule->getPriceList()->getId()),
            'unit' => $unitValue,
            'currency' => $currencyValue,
            'quantity' => $quantityValue,
            'priceRule' => (string)$qb->expr()->literal($rule->getId()),
            'value' => $priceValue,
        ];
        $this->addSelectInOrder($qb, $this->qbSelectPart);
        $qb->andWhere($qb->expr()->gte($priceValue, 0));
        $this->applyParameters($qb, $params);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $expression
     * @param array $params
     * @return string
     */
    protected function getValueByExpression(QueryBuilder $qb, $expression, array $params)
    {
        return (string)$this->expressionBuilder->convert(
            $this->expressionParser->parse($expression),
            $qb->expr(),
            $params,
            $this->queryConverter->getTableAliasByColumn()
        );
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     */
    protected function applyRuleConditions(QueryBuilder $qb, PriceRule $rule)
    {
        $additionalConditions = $this->getAdditionalConditions($rule);
        $conditions = [];
        $condition = $this->getProcessedRuleCondition($rule);
        if ($condition) {
            $conditions[] = '(' . $condition . ')';
        }
        if ($additionalConditions) {
            $conditions[] = '(' . $additionalConditions . ')';
        }
        $condition = implode(' and ', $conditions);

        if ($condition) {
            $params = [];
            $qb->andWhere(
                $this->expressionBuilder->convert(
                    $this->expressionParser->parse($condition),
                    $qb->expr(),
                    $params,
                    $this->queryConverter->getTableAliasByColumn()
                )
            );
            $this->applyParameters($qb, $params);
        }
    }

    /**
     * Manually entered prices should not be rewritten by generator.
     *
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     * @param string $rootAlias
     */
    protected function restrictByManualPrices(QueryBuilder $qb, PriceRule $rule, $rootAlias)
    {
        /** @var EntityManagerInterface $em */
        $em = $qb->getEntityManager();
        $subQb = $em->createQueryBuilder();
        $subQb->from(ProductPrice::class, 'productPriceManual')
            ->select('productPriceManual')
            ->where(
                $subQb->expr()->andX(
                    $subQb->expr()->eq('productPriceManual.product', $rootAlias),
                    $subQb->expr()->eq('productPriceManual.priceList', ':priceListManual'),
                    sprintf('productPriceManual.unit = %s', $this->qbSelectPart['unit']),
                    sprintf('productPriceManual.currency = %s', $this->qbSelectPart['currency']),
                    sprintf('productPriceManual.quantity = %s', $this->qbSelectPart['quantity'])
                )
            );

        $qb->setParameter('priceListManual', $rule->getPriceList()->getId())
            ->andWhere(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $subQb->getQuery()->getDQL()
                    )
                )
            );
    }

    /**
     * @param PriceRule $rule
     * @param QueryBuilder $qb
     * @param string $rootAlias
     */
    protected function restrictByAssignedProducts(PriceRule $rule, QueryBuilder $qb, $rootAlias)
    {
        $qb
            ->join(
                PriceListToProduct::class,
                'priceListToProduct',
                Join::WITH,
                $qb->expr()->eq('priceListToProduct.product', $rootAlias)
            )
            ->andWhere($qb->expr()->eq('priceListToProduct.priceList', ':priceList'))
            ->setParameter('priceList', $rule->getPriceList()->getId());
    }

    /**
     * @param QueryBuilder $qb
     * @param Product $product
     */
    protected function restrictByGivenProduct(QueryBuilder $qb, Product $product = null)
    {
        if ($product) {
            $qb->andWhere($qb->expr()->eq($this->getRootAlias($qb), ':product'))
                ->setParameter('product', $product->getId());
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     * @param string $rootAlias
     */
    protected function restrictBySupportedUnits(QueryBuilder $qb, PriceRule $rule, $rootAlias)
    {
        if ($rule->getProductUnitExpression()) {
            $qb->join($rootAlias.'.unitPrecisions', '_allowedUnit')
                ->andWhere(
                    $qb->expr()->eq(
                        '_allowedUnit.unit',
                        (string)$this->getValueByExpression($qb, $rule->getProductUnitExpression(), [])
                    )
                );
        } else {
            $qb->join($rootAlias.'.unitPrecisions', '_allowedUnit')
                ->andWhere($qb->expr()->eq('_allowedUnit.unit', ':requiredUnitUnit'))
                ->setParameter('requiredUnitUnit', $rule->getProductUnit());
        }
    }

    /**
     * In query result set all joined relations should have currency allowed in price list
     *
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     */
    protected function restrictBySupportedCurrencies(QueryBuilder $qb, PriceRule $rule)
    {
        if ($rule->getCurrencyExpression()) {
            $qb->andWhere(
                $qb->expr()->in(
                    (string)$this->getValueByExpression($qb, $rule->getCurrencyExpression(), []),
                    $rule->getPriceList()->getCurrencies()
                )
            );
        } else {
            // Skip rules that are not in price list supported currencies
            if (!in_array($rule->getCurrency(), $rule->getPriceList()->getCurrencies(), true)) {
                $qb->andWhere('1 = 0');
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     */
    protected function restrictBySupportedQuantity(QueryBuilder $qb, PriceRule $rule)
    {
        if ($rule->getQuantityExpression()) {
            $quantityValue = (string)$this->getValueByExpression($qb, $rule->getQuantityExpression(), []);
            $qb->andWhere($qb->expr()->gte($quantityValue, 0));
        }
    }

    /**
     * @param NodeInterface $node
     */
    protected function saveUsedPriceRelations(NodeInterface $node)
    {
        foreach ($node->getNodes() as $subNode) {
            if ($subNode instanceof RelationNode) {
                $realClass = $this->fieldsProvider->getRealClassName($subNode->getRelationAlias());
                if (is_a($realClass, BaseProductPrice::class, true)) {
                    $this->usedPriceRelations[$subNode->getResolvedContainer()] = $this->requiredPriceConditions;
                }
            }
        }
    }

    /**
     * Add additional unit, quantity and currency for price.
     *
     * Prices contains unit, quantity and currency. If not of them are mentioned in rule condition, then them are added
     * automatically based on unit, quantity and currency selected in rule.
     *
     * @param PriceRule $rule
     * @return string
     */
    protected function getAdditionalConditions(PriceRule $rule)
    {
        $ruleCondition = $this->getProcessedRuleCondition($rule);
        $reverseNameMapping = $this->expressionParser->getReverseNameMapping();
        if ($ruleCondition) {
            $parsedCondition = $this->expressionParser->parse($ruleCondition);
            foreach ($parsedCondition->getNodes() as $node) {
                if ($node instanceof RelationNode) {
                    $relationAlias = $node->getResolvedContainer();
                    if (!empty($this->usedPriceRelations[$relationAlias][$node->getRelationField()])) {
                        $this->usedPriceRelations[$relationAlias][$node->getRelationField()] = false;
                    }
                }
            }
        }

        $generatedConditions = [];
        foreach ($this->usedPriceRelations as $alias => $relationFields) {
            list($root, $field) = explode('::', $alias);
            $containerId = null;
            if (strpos($field, '|') !== false) {
                list($field, $containerId) = explode('|', $field);
            }
            $root = $reverseNameMapping[$root];

            foreach ($relationFields as $relationField => $requiredField) {
                if ($requiredField) {
                    $generatedConditions[] = $this->getAdditionalCondition(
                        $rule,
                        $root,
                        $field,
                        $relationField,
                        $containerId
                    );
                }
            }
        }

        return implode(' and ', $this->getFilteredAdditionalConditions($generatedConditions));
    }

    /**
     * Filter pointless conditions.
     *
     * Avoid expressions like: "product.msrp.currency == product.msrp.currency"
     *
     * @param array $conditions
     * @return array
     */
    protected function getFilteredAdditionalConditions(array $conditions)
    {
        return array_filter(
            $conditions,
            function ($condition) {
                if (null === $condition) {
                    return false;
                }

                $parts = explode('==', $condition);
                if (count($parts) < 2 || trim($parts[0]) === trim($parts[1])) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * Get additional condition string.
     *
     * Return condition string in format "root.field.relationField == ?"
     * or "root[containerId].field.relationField == ?" if container is not null,
     * where ? is string or number depend on field type in case when rule expression is defined,
     * this expression is used
     *
     * @param PriceRule $rule
     * @param string $root
     * @param string $field
     * @param string $relationField
     * @param null|int $containerId
     * @return null|string
     */
    protected function getAdditionalCondition(PriceRule $rule, $root, $field, $relationField, $containerId = null)
    {
        $conditionTemplate = '%1$s.%2$s.%3$s == ';
        if ($containerId) {
            $conditionTemplate = '%1$s[%5$d].%2$s.%3$s == ';
        }
        $conditionVariables = [$root, $field, $relationField];
        switch ($relationField) {
            case 'currency':
                $conditionTemplate .= '%4$s';
                if ($rule->getCurrencyExpression()) {
                    $conditionVariables[] = $rule->getCurrencyExpression();
                } else {
                    $conditionVariables[] = sprintf("'%s'", $rule->getCurrency());
                }
                break;

            case 'unit':
                $conditionTemplate .= '%4$s';
                if ($rule->getProductUnitExpression()) {
                    $conditionVariables[] = $rule->getProductUnitExpression();
                } else {
                    $conditionVariables[] = sprintf("'%s'", $rule->getProductUnit()->getCode());
                }
                break;

            case 'quantity':
                if ($rule->getQuantityExpression()) {
                    $conditionTemplate .= '%4$s';
                    $conditionVariables[] = $this->getBaseQuantityExpression($rule);
                } else {
                    $conditionTemplate .= '%4$f';
                    $conditionVariables[] = $rule->getQuantity();
                }
                break;
        }

        array_unshift($conditionVariables, $conditionTemplate);
        if ($containerId) {
            $conditionVariables[] = $containerId;
        }

        return call_user_func_array('sprintf', $conditionVariables);
    }

    /**
     * Here we assume that quantity expression should use only one relation
     *
     * @param PriceRule $rule
     * @return string
     */
    protected function getBaseQuantityExpression(PriceRule $rule)
    {
        $parsedCondition = $this->expressionParser->parse($rule->getQuantityExpression());
        foreach ($parsedCondition->getNodes() as $node) {
            if ($node instanceof RelationNode) {
                $nodeRoot = $this->expressionParser->getReverseNameMapping()[$node->getContainer()];
                if ($node->getContainerId()) {
                    return sprintf(
                        '%s[%d].%s.%s',
                        $nodeRoot,
                        $node->getContainerId(),
                        $node->getField(),
                        $node->getRelationField()
                    );
                } else {
                    return sprintf(
                        '%s.%s.%s',
                        $nodeRoot,
                        $node->getField(),
                        $node->getRelationField()
                    );
                }
            }
        }
        return '';
    }

    /**
     * @param QueryBuilder $qb
     * @return string
     */
    protected function getRootAlias(QueryBuilder $qb)
    {
        $aliases = $qb->getRootAliases();

        return reset($aliases);
    }

    /**
     * @param PriceRule $rule
     * @return string
     */
    protected function getProcessedRuleCondition(PriceRule $rule)
    {
        return $this->expressionPreprocessor->process($rule->getRuleCondition());
    }
}
