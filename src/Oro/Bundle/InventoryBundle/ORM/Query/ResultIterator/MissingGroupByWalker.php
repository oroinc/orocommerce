<?php

namespace Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\TreeWalkerAdapter;

/**
 * Changes the groupByClause of the AST and adds missed columns.
 */
class MissingGroupByWalker extends TreeWalkerAdapter
{
    /**
     * {@inheritDoc}
     *
     * Complexity suppressed due to too complex AST structure
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $groupBys = [];
        $AST->groupByClause = $AST->groupByClause ?: new AST\GroupByClause([]);
        // get current group by elements in a simple array
        foreach ($AST->groupByClause->groupByItems as $groupByItem) {
            if (!$groupByItem instanceof AST\PathExpression) {
                continue;
            }
            $groupBys[$groupByItem->identificationVariable] = $groupByItem->field;
        }

        $selectExpressions = array_map(
            function (AST\SelectExpression $expression) {
                return $expression->expression;
            },
            $AST->selectClause->selectExpressions
        );

        $newGroupBys = [];
        // parse query components and make each one has a group by section
        foreach ($this->_getQueryComponents() as $componentAlias => $component) {
            if (!array_key_exists('metadata', $component)) {
                continue;
            }
            if (!in_array($componentAlias, $selectExpressions)) {
                continue;
            }
            if (!isset($groupBys[$componentAlias])) {
                $idField = $component['metadata']->getSingleIdentifierFieldName();
                $pathExpression = new AST\PathExpression(
                    AST\PathExpression::TYPE_STATE_FIELD | AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                    $componentAlias,
                    $idField
                );
                $pathExpression->type = AST\PathExpression::TYPE_STATE_FIELD;

                $newGroupBys[] = $pathExpression;
            }
        }

        // add new group by's to abstract syntax tree
        $AST->groupByClause->groupByItems = array_merge($AST->groupByClause->groupByItems, $newGroupBys);
    }
}
