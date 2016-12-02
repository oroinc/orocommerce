<?php

namespace Oro\Bundle\CustomerBundle\EventListener\Datagrid;

use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class AccountUserRoleDatagridListener
{
    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $dataGrid = $event->getDatagrid();

        $datasource = $dataGrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $qb = $datasource->getQueryBuilder();
            $alias = $qb->getRootAliases()[0];
            $criteria = new Criteria();
            $this->aclHelper->applyAclToCriteria(
                AccountUserRole::class,
                $criteria,
                'VIEW',
                ['account' => $alias.'.account', 'organization' => $alias.'.organization']
            );

            $qb->addCriteria($criteria);
            $qb->orWhere(
                $alias . '.selfManaged = :isActive AND '.
                $alias . '.public = :isActive AND '.
                $alias . '.account is NULL'
            );
            $qb->setParameter('isActive', true, \PDO::PARAM_BOOL);
        }
    }
}
