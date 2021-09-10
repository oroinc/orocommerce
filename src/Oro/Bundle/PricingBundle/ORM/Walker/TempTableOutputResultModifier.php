<?php

namespace Oro\Bundle\PricingBundle\ORM\Walker;

use Doctrine\ORM\Query\AST;
use Oro\Component\DoctrineUtils\ORM\Walker\AbstractOutputResultModifier;

/**
 * Query walker for temp table
 */
class TempTableOutputResultModifier extends AbstractOutputResultModifier
{
    public const ORO_TEMP_TABLE_ALIASES = 'oro_pricing.temp_table_aliases';

    /**
     * {@inheritdoc}
     */
    public function walkSubselectFromClause($subselectFromClause, string $result)
    {
        if ($this->isApplicable()) {
            return $this->replaceFrom($subselectFromClause, $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause, string $result)
    {
        if ($this->isApplicable()) {
            return $this->replaceFrom($fromClause, $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkJoin($join, string $result)
    {
        if ($this->isApplicable()) {
            return $this->replaceJoin($join, $result);
        }

        return $result;
    }

    /**
     * @param AST\FromClause|AST\SubselectFromClause $fromClause
     * @param string $result
     * @return string|string[]|null
     */
    private function replaceFrom(AST\Node $fromClause, string $result)
    {
        $aliasToTable = $this->getQuery()->getHint(self::ORO_TEMP_TABLE_ALIASES);
        /** @var AST\IdentificationVariableDeclaration $declaration */
        foreach ($fromClause->identificationVariableDeclarations as $declaration) {
            $alias = $declaration->rangeVariableDeclaration->aliasIdentificationVariable;
            if (array_key_exists($alias, $aliasToTable)) {
                return preg_replace('/(\s*FROM)\s+(\w+)\s+(\w+)/i', '$1 ' . $aliasToTable[$alias] .  ' $3', $result);
            }
        }

        return $result;
    }

    /**
     * @param AST\Join $join
     * @param string $result
     * @return string|string[]|null
     */
    private function replaceJoin(AST\Join $join, string $result)
    {
        $aliasToTable = $this->getQuery()->getHint(self::ORO_TEMP_TABLE_ALIASES);
        /** @var AST\RangeVariableDeclaration $declaration */
        $declaration = $join->joinAssociationDeclaration;
        $alias = $declaration->aliasIdentificationVariable;
        if (array_key_exists($alias, $aliasToTable)) {
            return preg_replace('/(\s*JOIN)\s+(\w+)\s+(\w+)/i', '$1 ' . $aliasToTable[$alias] .  ' $3', $result);
        }

        return $result;
    }

    private function isApplicable(): bool
    {
        return $this->getQuery()->hasHint(self::ORO_TEMP_TABLE_ALIASES);
    }
}
