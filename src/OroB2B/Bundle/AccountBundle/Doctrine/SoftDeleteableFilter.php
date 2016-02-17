<?php

namespace OroB2B\Bundle\AccountBundle\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class SoftDeleteableFilter extends SQLFilter
{

    /**
     * Gets the SQL query part to add to a query.
     *
     * @param ClassMetaData $targetEntity
     * @param string $targetTableAlias
     *
     * @return string The constraint SQL if there is available, empty string otherwise.
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->reflClass
                ->implementsInterface('OroB2B\Bundle\AccountBundle\Doctrine\SoftDeleateableInterface')
        ) {
            return '1 = 1';
        }
        return '2 = 2';
    }
}
