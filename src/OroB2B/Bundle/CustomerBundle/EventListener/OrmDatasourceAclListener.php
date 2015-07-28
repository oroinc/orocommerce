<?php

namespace OroB2B\Bundle\CustomerBundle\EventListener;

use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\Subselect;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class OrmDatasourceAclListener
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var MetadataProviderInterface
     */
    protected $metadataProvider;

    /**
     * @param SecurityFacade $securityFacade
     * @param MetadataProviderInterface $metadataProvider
     */
    public function __construct(SecurityFacade $securityFacade, MetadataProviderInterface $metadataProvider)
    {
        $this->securityFacade = $securityFacade;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * @param OrmResultBefore $event
     */
    public function onResultBefore(OrmResultBefore $event)
    {
        if ($this->securityFacade->getLoggedUser() instanceof User) {
            return;
        }

        $config = $event->getDatagrid()->getConfig();
        $query = $event->getQuery();

        /** @var Subselect|SelectStatement $select */
        $select = $query->getAST();
        $fromClause = $select instanceof SelectStatement ? $select->fromClause : $select->subselectFromClause;

        $skipAclCheck = true;

        /** @var IdentificationVariableDeclaration $identificationVariableDeclaration */
        foreach ($fromClause->identificationVariableDeclarations as $identificationVariableDeclaration) {
            $entityName = $identificationVariableDeclaration->rangeVariableDeclaration->abstractSchemaName;
            $metadata = $this->metadataProvider->getMetadata($entityName);

            if ($metadata->hasOwner()) {
                $skipAclCheck = false;
                break;
            }
        }

        if ($skipAclCheck) {
            $config->offsetSetByPath(Builder::DATASOURCE_SKIP_ACL_CHECK, true);
        }
    }
}
