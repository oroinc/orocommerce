<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\Subselect;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;

class OrmDatasourceAclListener
{
    /**
     * @var MetadataProviderInterface
     */
    protected $metadataProvider;

    /**
     * @param MetadataProviderInterface $metadataProvider
     */
    public function __construct(MetadataProviderInterface $metadataProvider)
    {
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * @param OrmResultBefore $event
     */
    public function onResultBefore(OrmResultBefore $event)
    {
        $config = $event->getDatagrid()->getConfig();
        $query = $event->getQuery();

        /** @var Subselect|SelectStatement $select */
        $select = $query->getAST();
        $fromClause = $select instanceof SelectStatement ? $select->fromClause : $select->subselectFromClause;

        $entitiesWithoutOwnership = [];

        /** @var IdentificationVariableDeclaration $identificationVariableDeclaration */
        foreach ($fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $entityName = $identificationVariableDeclaration->rangeVariableDeclaration->abstractSchemaName;
            $metadata = $this->metadataProvider->getMetadata($entityName);

            if (!$metadata->hasOwner()) {
                $entitiesWithoutOwnership[] = $entityName;
            }
        }

        if (count($entitiesWithoutOwnership) &&
            count($entitiesWithoutOwnership) === count($fromClause->identificationVariableDeclarations)
        ) {
            $config->offsetSetByPath(Builder::DATASOURCE_SKIP_ACL_CHECK, true);
        }
    }
}
