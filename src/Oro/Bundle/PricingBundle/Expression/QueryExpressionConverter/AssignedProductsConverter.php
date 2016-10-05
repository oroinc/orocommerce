<?php

namespace Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Expression\BinaryNode;
use Oro\Bundle\PricingBundle\Expression\ContainerHolderNodeInterface;
use Oro\Bundle\PricingBundle\Expression\NameNode;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\RelationNode;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

class AssignedProductsConverter implements QueryExpressionConverterInterface
{
    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldsProvider;

    /**
     * @param PriceRuleFieldsProvider $fieldsProvider
     */
    public function __construct(PriceRuleFieldsProvider $fieldsProvider)
    {
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        if ($node instanceof BinaryNode) {
            $operation = $node->getOperation();
            if ($operation === 'in' || $operation === 'not in') {
                /** @var ContainerHolderNodeInterface $right */
                $right = $node->getRight();
                if ($right instanceof NameNode
                    && $right->getContainer() === PriceList::class && $right->getField() === 'assignedProducts'
                ) {
                    /** @var ContainerHolderNodeInterface|NodeInterface $left */
                    $left = $node->getLeft();
                    $this->assertLeftOperand($left);

                    $limitationDql = sprintf(
                        'SELECT 1 FROM %s _ap WHERE _ap.product = %s AND _ap.priceList = %s',
                        PriceListToProduct::class,
                        $this->getTableAliasByNode($aliasMapping, $left),
                        $this->getTableAliasByNode($aliasMapping, $right)
                    );

                    $expression = $expr->exists($limitationDql);
                    if ($operation === 'not in') {
                        $expression = $expr->not($expression);
                    }

                    return $expression;
                }
            }
        }

        return null;
    }

    /**
     * @param NodeInterface $left
     */
    protected function assertLeftOperand(NodeInterface $left)
    {
        $isAllowedNode = false;
        if ($left instanceof NameNode) {
            $isAllowedNode = $left->getContainer() === Product::class && $left->getField() === 'id';
        } elseif ($left instanceof RelationNode) {
            $relationClass = $this->fieldsProvider->getRealClassName($left->getContainer(), $left->getField());
            $isAllowedNode = $relationClass === Product::class && $left->getRelationField() === 'id';
        }

        if (!$isAllowedNode) {
            throw new \InvalidArgumentException(
                'Left operand of in operation for assigned products condition must be product identifier field'
            );
        }
    }

    /**
     * @param array $aliasMapping
     * @param ContainerHolderNodeInterface $node
     * @return string
     */
    protected function getTableAliasByNode(array $aliasMapping, ContainerHolderNodeInterface $node)
    {
        $aliasKey = $node->getResolvedContainer();
        if (array_key_exists($aliasKey, $aliasMapping)) {
            return $aliasMapping[$aliasKey];
        }

        throw new \InvalidArgumentException(
            sprintf('No table alias found for "%s"', $aliasKey)
        );
    }
}
