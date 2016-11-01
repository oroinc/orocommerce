<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;

interface QueryExpressionConverterInterface
{
    /**
     * @param NodeInterface $node
     * @param Expr $expr
     * @param array $params
     * @param array $aliasMapping
     * @return Expr\Base|string|null
     */
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = []);
}
