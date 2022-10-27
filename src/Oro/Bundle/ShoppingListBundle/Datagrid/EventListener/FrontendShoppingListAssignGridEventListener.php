<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Adds addition rule to the where clause to ensure that only users who can be assigned to the current shopping list
 * are shown in the assign-grid.
 */
class FrontendShoppingListAssignGridEventListener
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var AclHelper */
    private $aclHelper;

    public function __construct(ManagerRegistry $registry, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $config = $event->getConfig();

        $query = $config->getOrmQuery();
        if (!$query) {
            return;
        }

        $rootAlias = $query->getRootAlias();
        if (!$rootAlias) {
            return;
        }

        $config->offsetAddToArrayByPath(
            OrmQueryConfiguration::WHERE_AND_PATH,
            [sprintf('%s.id IN (:customer_user_ids)', $rootAlias)]
        );
        $config->offsetAddToArrayByPath(DatagridConfiguration::DATASOURCE_BIND_PARAMETERS_PATH, ['customer_user_ids']);

        $event->getDatagrid()
            ->getParameters()
            ->set(
                'customer_user_ids',
                $this->registry->getManagerForClass(CustomerUser::class)
                    ->getRepository(CustomerUser::class)
                    ->getAssignableCustomerUserIds($this->aclHelper, ShoppingList::class)
            );
    }
}
