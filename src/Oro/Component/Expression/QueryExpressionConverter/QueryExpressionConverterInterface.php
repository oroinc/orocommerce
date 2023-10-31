<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;

/**
 * Interface for converters to convert Oro\Component\Expression\Node\NodeInterface nodes to query builder expressions.
 */
interface QueryExpressionConverterInterface
{
    public const REQUIRE_PARAMETRIZATION = '_parametrize_';
    public const MAPPING_TABLES = 'tables';
    public const MAPPING_COLUMNS = 'columns';

    /**
     * @param NodeInterface $node
     * @param Expr $expr
     * @param array $params
     * @param array $aliasMapping
     * @return Expr\Base|string|null
     */
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = []);
}
