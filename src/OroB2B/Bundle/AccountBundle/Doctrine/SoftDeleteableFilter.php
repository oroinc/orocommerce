<?php

namespace Oro\Bundle\AccountBundle\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class SoftDeleteableFilter extends SQLFilter
{
    const FILTER_ID = 'soft_deleteable';

    /**
     * @var EntityManager
     */
    protected $em;

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
        if (!$targetEntity->reflClass->implementsInterface(SoftDeleteableInterface::NAME)) {
            return '';
        }

        $connection = $this->getEm()->getConnection();
        $platform = $connection->getDatabasePlatform();
        $column = $this->getEm()
            ->getConfiguration()
            ->getQuoteStrategy()
            ->getColumnName(SoftDeleteableInterface::FIELD_NAME, $targetEntity, $platform);

        return $platform->getIsNullExpression($targetTableAlias . '.' . $column);
    }

    /**
     * @param EntityManager $em
     */
    public function setEm($em)
    {
        $this->em = $em;
    }

    /**
     * @return EntityManager
     * @throws \InvalidArgumentException
     */
    public function getEm()
    {
        if (!$this->em) {
            throw new \InvalidArgumentException('EntityManager injection required.');
        }

        return $this->em;
    }
}
