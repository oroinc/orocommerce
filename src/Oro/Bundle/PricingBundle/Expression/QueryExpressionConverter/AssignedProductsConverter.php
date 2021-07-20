<?php

namespace Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;

class AssignedProductsConverter implements QueryExpressionConverterInterface
{
    /**
     * @var FieldsProviderInterface
     */
    protected $fieldsProvider;

    public function __construct(FieldsProviderInterface $fieldsProvider)
    {
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(Node\NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        if ($node instanceof Node\BinaryNode) {
            $operation = $node->getOperation();
            if ($operation === 'in' || $operation === 'not in') {
                /** @var Node\ContainerHolderNodeInterface $right */
                $right = $node->getRight();
                if ($right instanceof Node\NameNode
                    && $right->getContainer() === PriceList::class && $right->getField() === 'assignedProducts'
                ) {
                    /** @var Node\ContainerHolderNodeInterface|Node\NodeInterface $left */
                    $left = $node->getLeft();
                    $this->assertLeftOperand($left);

                    $alias = '_ap' . $right->getContainerId();
                    QueryBuilderUtil::checkIdentifier($alias);
                    $limitationDql = sprintf(
                        'SELECT 1 FROM %1$s %2$s WHERE %2$s.product = %3$s AND %2$s.priceList = %4$s',
                        PriceListToProduct::class,
                        $alias,
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

    protected function assertLeftOperand(Node\NodeInterface $left)
    {
        $isAllowedNode = false;
        if ($left instanceof Node\NameNode) {
            $isAllowedNode = $left->getContainer() === Product::class && $left->getField() === 'id';
        } elseif ($left instanceof Node\RelationNode) {
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
     * @param Node\ContainerHolderNodeInterface $node
     * @return string
     */
    protected function getTableAliasByNode(array $aliasMapping, Node\ContainerHolderNodeInterface $node)
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
