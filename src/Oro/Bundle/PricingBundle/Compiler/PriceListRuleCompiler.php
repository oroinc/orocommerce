<?php

namespace Oro\Bundle\PricingBundle\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node;

/**
 * Compile price rule to QueryBuilder with all applied restrictions.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PriceListRuleCompiler extends AbstractRuleCompiler
{
    /**
     * @var array
     */
    protected static $fieldsOrder = [
        'id',
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
     * @var FieldsProviderInterface
     */
    protected $fieldsProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var array
     */
    protected $usedPriceRelations = [];

    /**
     * @var array
     */
    protected $qbSelectPart = [];

    public function setFieldsProvider(FieldsProviderInterface $fieldsProvider)
    {
        $this->fieldsProvider = $fieldsProvider;
    }

    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param PriceRule $rule
     * @param array|Product[] $products
     * @return QueryBuilder
     */
    public function compile(PriceRule $rule, array $products = [])
    {
        if (!$rule->getId()) {
            throw new \InvalidArgumentException(
                sprintf('Cannot compile price list rule: %s was expected to have id', PriceRule::class)
            );
        }

        $cacheKey = 'pr_' . $rule->getId();
        $qb = $this->cache->fetch($cacheKey);
        if (!$qb) {
            $qb = $this->compileQueryBuilder($rule);

            $this->cache->save($cacheKey, $qb);
        }

        $this->restrictByGivenProduct($qb, $products);

        return $qb;
    }

    public function compileQueryBuilder(PriceRule $rule): QueryBuilder
    {
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

        $precision = $this->configManager->get('oro_pricing.price_calculation_precision');
        if ($precision !== null && $precision !== '') {
            $priceValue = sprintf('ROUND(%s, %d)', $priceValue, (int)$precision);
        }

        if ($rule->getCurrencyExpression()) {
            $currencyValue = (string)$this->getValueByExpression($qb, $rule->getCurrencyExpression(), $params);
        } else {
            $currencyValue = (string)$qb->expr()->literal((string)$rule->getCurrency());
        }

        if ($rule->getQuantityExpression()) {
            $quantityValue = (string)$this->getValueByExpression($qb, $rule->getQuantityExpression(), $params);
        } else {
            $quantityValue = (string)$qb->expr()->literal((float)$rule->getQuantity());
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
            'id' => 'UUID()',
            'product' => $rootAlias . '.id',
            'productSku' => $rootAlias . '.sku',
            'priceList' => (string)$qb->expr()->literal((int)$rule->getPriceList()->getId()),
            'unit' => $unitValue,
            'currency' => $currencyValue,
            'quantity' => $quantityValue,
            'priceRule' => (string)$qb->expr()->literal((int)$rule->getId()),
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
     * @param array|Product[] $products
     */
    protected function restrictByGivenProduct(QueryBuilder $qb, array $products = [])
    {
        if ($products) {
            $qb->andWhere($qb->expr()->in($this->getRootAlias($qb), ':products'))
                ->setParameter('products', $products);
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
            $qb->join($rootAlias . '.unitPrecisions', '_allowedUnit')
                ->andWhere(
                    $qb->expr()->eq(
                        '_allowedUnit.unit',
                        (string)$this->getValueByExpression($qb, $rule->getProductUnitExpression(), [])
                    )
                );
        } else {
            $qb->join($rootAlias . '.unitPrecisions', '_allowedUnit')
                ->andWhere($qb->expr()->eq('_allowedUnit.unit', ':requiredUnitUnit'))
                ->setParameter('requiredUnitUnit', $rule->getProductUnit());
        }
    }

    /**
     * In query result set all joined relations should have currency allowed in price list
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

    protected function restrictBySupportedQuantity(QueryBuilder $qb, PriceRule $rule)
    {
        if ($rule->getQuantityExpression()) {
            $quantityValue = (string)$this->getValueByExpression($qb, $rule->getQuantityExpression(), []);
            $qb->andWhere($qb->expr()->gte($quantityValue, 0));
        }
    }

    protected function saveUsedPriceRelations(Node\NodeInterface $node)
    {
        foreach ($node->getNodes() as $subNode) {
            if ($subNode instanceof Node\RelationNode) {
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
     * automatically based on unit, quantity and currency selected in rule. For price attributes quantity is always
     * added as 1.0
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
                if ($node instanceof Node\RelationNode) {
                    $relationAlias = $node->getResolvedContainer();
                    if (!empty($this->usedPriceRelations[$relationAlias][$node->getRelationField()])) {
                        $this->usedPriceRelations[$relationAlias][$node->getRelationField()] = false;
                    }
                }
            }
        }

        $generatedConditions = [];
        foreach ($this->usedPriceRelations as $alias => $relationFields) {
            [$root, $field] = explode('::', $alias);
            $containerId = null;
            if (str_contains($field, '|')) {
                [$field, $containerId] = explode('|', $field);
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
            static function ($condition) {
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
        $valueTemplate = '%s';
        $value = null;
        switch ($relationField) {
            case 'currency':
                if ($rule->getCurrencyExpression()) {
                    $value = $rule->getCurrencyExpression();
                } else {
                    $value = sprintf("'%s'", $rule->getCurrency());
                }
                break;

            case 'unit':
                if ($rule->getProductUnitExpression()) {
                    $value = $rule->getProductUnitExpression();
                } else {
                    $value = sprintf("'%s'", $rule->getProductUnit()->getCode());
                }
                break;

            case 'quantity':
                $namesMapping = $this->expressionParser->getNamesMapping();
                $rootClass = $namesMapping[$root];
                $relationClass = $this->fieldsProvider->getRealClassName($rootClass, $field);

                if (is_a($relationClass, PriceAttributeProductPrice::class, true)) {
                    $valueTemplate = '%f';
                    $value = 1;
                } elseif ($rule->getQuantityExpression()) {
                    $value = $this->getBaseQuantityExpression($rule);
                } else {
                    $valueTemplate = '%f';
                    $value = $rule->getQuantity();
                }
                break;
        }

        if ($containerId) {
            return sprintf('%s[%d].%s.%s == ' . $valueTemplate, $root, $containerId, $field, $relationField, $value);
        }

        return sprintf('%s.%s.%s == ' . $valueTemplate, $root, $field, $relationField, $value);
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
            if ($node instanceof Node\RelationNode) {
                $nodeRoot = $this->expressionParser->getReverseNameMapping()[$node->getContainer()];
                if ($node->getContainerId()) {
                    return sprintf(
                        '%s[%d].%s.%s',
                        $nodeRoot,
                        $node->getContainerId(),
                        $node->getField(),
                        $node->getRelationField()
                    );
                }

                return sprintf(
                    '%s.%s.%s',
                    $nodeRoot,
                    $node->getField(),
                    $node->getRelationField()
                );
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
