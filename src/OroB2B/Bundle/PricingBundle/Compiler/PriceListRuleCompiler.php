<?php

namespace OroB2B\Bundle\PricingBundle\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Expression\NodeInterface;
use OroB2B\Bundle\PricingBundle\Expression\RelationNode;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceListRuleCompiler extends AbstractRuleCompiler
{
    /**
     * @var array
     */
    protected $fieldsOrder = [
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
            $this->restrictBySupportedUnits($qb, $rule, $rootAlias);

            $this->modifySelectPart($qb, $rule, $rootAlias);
            $this->applyRuleConditions($qb, $rule);
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
        $ruleCondition = $rule->getRuleCondition();
        if ($ruleCondition) {
            $expression = sprintf('%s and (%s) > 0', $ruleCondition, $rule->getRule());
        } else {
            $expression = $rule->getRule();
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
        return $this->fieldsOrder;
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     * @param string $rootAlias
     */
    protected function modifySelectPart(QueryBuilder $qb, PriceRule $rule, $rootAlias)
    {
        $params = [];
        $priceValue = (string)$this->expressionBuilder->convert(
            $this->expressionParser->parse($rule->getRule()),
            $qb->expr(),
            $params,
            $this->queryConverter->getTableAliasByColumn()
        );
        $this->addSelectInOrder(
            $qb,
            [
                'product' => $rootAlias . '.id',
                'productSku' => $rootAlias . '.sku',
                'priceList' => (string)$qb->expr()->literal($rule->getPriceList()->getId()),
                'unit' => (string)$qb->expr()->literal($rule->getProductUnit()->getCode()),
                'currency' => (string)$qb->expr()->literal($rule->getCurrency()),
                'quantity' => (string)$qb->expr()->literal($rule->getQuantity()),
                'priceRule' => (string)$qb->expr()->literal($rule->getId()),
                'value' => $priceValue,
            ]
        );
        $qb->andWhere($qb->expr()->gte($priceValue, 0));
        $this->applyParameters($qb, $params);
    }

    /**
     * @param QueryBuilder $qb
     * @param PriceRule $rule
     */
    protected function applyRuleConditions(QueryBuilder $qb, PriceRule $rule)
    {
        $additionalConditions = $this->getAdditionalConditions($rule);
        $conditions = [];
        $condition = $rule->getRuleCondition();
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
                    $subQb->expr()->eq('productPriceManual.unit', ':unitManual'),
                    $subQb->expr()->eq('productPriceManual.currency', ':currencyManual'),
                    $subQb->expr()->eq('productPriceManual.quantity', ':quantityManual')
                )
            );

        $qb->setParameter('priceListManual', $rule->getPriceList()->getId())
            ->setParameter('unitManual', $rule->getProductUnit()->getCode())
            ->setParameter('currencyManual', $rule->getCurrency())
            ->setParameter('quantityManual', $rule->getQuantity())
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
        $qb
            ->join(
                $rootAlias . '.unitPrecisions',
                '_allowedUnit'
            )
            ->andWhere($qb->expr()->eq('_allowedUnit.unit', ':requiredUnitUnit'))
            ->setParameter('requiredUnitUnit', $rule->getProductUnit());
    }

    /**
     * @param NodeInterface $node
     */
    protected function saveUsedPriceRelations(NodeInterface $node)
    {
        foreach ($node->getNodes() as $subNode) {
            if ($subNode instanceof RelationNode) {
                $classAlias = $subNode->getRelationAlias();
                $realClass = $this->fieldsProvider->getRealClassName($classAlias);
                if ($realClass === PriceAttributeProductPrice::class) {
                    $this->usedPriceRelations[$classAlias] = $this->requiredPriceConditions;
                }
            }
        }
    }

    /**
     * @param PriceRule $rule
     * @return string
     */
    protected function getAdditionalConditions(PriceRule $rule)
    {
        $ruleCondition = $rule->getRuleCondition();
        $reverseNameMapping = $this->expressionParser->getReverseNameMapping();
        if ($ruleCondition) {
            $parsedCondition = $this->expressionParser->parse($ruleCondition);
            foreach ($parsedCondition->getNodes() as $node) {
                if ($node instanceof RelationNode) {
                    $relationAlias = $node->getRelationAlias();
                    if (!empty($this->usedPriceRelations[$relationAlias][$node->getRelationField()])) {
                        $this->usedPriceRelations[$relationAlias][$node->getRelationField()] = false;
                    }
                }
            }
        }

        $generatedConditions = [];
        foreach ($this->usedPriceRelations as $alias => $relationFields) {
            list($root, $field) = explode('::', $alias);
            $root = $reverseNameMapping[$root];

            foreach ($relationFields as $relationField => $requiredField) {
                if ($requiredField) {
                    $generatedConditions[] = $this->getAdditionalCondition($rule, $root, $field, $relationField);
                }
            }
        }

        return implode(' and ', $generatedConditions);
    }

    /**
     * @param PriceRule $rule
     * @param string $root
     * @param string $field
     * @param string $relationField
     * @return null|string
     */
    protected function getAdditionalCondition(PriceRule $rule, $root, $field, $relationField)
    {
        $additionalCondition = null;
        switch ($relationField) {
            case 'currency':
                $additionalCondition = sprintf(
                    "%s.%s.%s == '%s'",
                    $root,
                    $field,
                    $relationField,
                    $rule->getCurrency()
                );
                break;

            case 'unit':
                $additionalCondition = sprintf(
                    "%s.%s.%s == '%s'",
                    $root,
                    $field,
                    $relationField,
                    $rule->getProductUnit()->getCode()
                );
                break;

            case 'quantity':
                $additionalCondition = sprintf(
                    '%s.%s.%s == %f',
                    $root,
                    $field,
                    $relationField,
                    $rule->getQuantity()
                );
                break;
        }

        return $additionalCondition;
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
}
