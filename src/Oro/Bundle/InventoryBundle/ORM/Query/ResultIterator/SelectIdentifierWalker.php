<?php

namespace Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Query\AST;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\SelectIdentifierWalker as BaseWalker;

class SelectIdentifierWalker extends BaseWalker
{
    /**
     * {@inheritdoc}
     *
     * Complexity suppressed due to too complex AST structure
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $this->validate($AST);

        // Get the root entity and alias from the AST fromClause
        $queryComponents = $this->_getQueryComponents();
        $from = $AST->fromClause->identificationVariableDeclarations;
        if (count($from) !== 1) {
            throw new \LogicException('You have to have exactly 1 From clause');
        }
        $fromRoot = reset($from);
        $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass = $queryComponents[$rootAlias]['metadata'];
        $identifierFieldName = $rootClass->getSingleIdentifierFieldName();

        $pathType = AST\PathExpression::TYPE_STATE_FIELD;
        if (isset($rootClass->associationMappings[$identifierFieldName])) {
            $pathType = AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        }

        $pathExpression = new AST\PathExpression(
            AST\PathExpression::TYPE_STATE_FIELD | AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $rootAlias,
            $identifierFieldName
        );
        $pathExpression->type = $pathType;

        $this->ensureGroupByForRootEntity($AST, $pathExpression, $rootAlias, $identifierFieldName);

        $AST->selectClause->selectExpressions = [new AST\SelectExpression($pathExpression, null)];
    }

    /**
     * @param AST\SelectStatement $AST
     * @param AST\PathExpression $pathExpression
     * @param string $rootAlias
     * @param string $identifierFieldName
     */
    protected function ensureGroupByForRootEntity(
        AST\SelectStatement $AST,
        AST\PathExpression $pathExpression,
        $rootAlias,
        $identifierFieldName
    ) {
        // Check possible primary key inconsistency cause by 'Group' sql clause
        $usedInGroupBy = false;
        if (isset($AST->groupByClause)) {
            foreach ($AST->groupByClause->groupByItems as $groupBy) {
                if ($groupBy instanceof AST\PathExpression) {
                    if ($groupBy->identificationVariable === $rootAlias && $groupBy->field === $identifierFieldName) {
                        $usedInGroupBy = true;
                        break;
                    }
                }
            }

            if (!$usedInGroupBy) {
                // if not present, add the primary key of root entity to group by
                $AST->groupByClause = $AST->groupByClause ?: new AST\GroupByClause([]);
                $AST->groupByClause->groupByItems = array_merge($AST->groupByClause->groupByItems, [$pathExpression]);
            }
        }
    }
}
