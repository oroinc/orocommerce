<?php

namespace OroB2B\Bundle\AccountBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserRoleDatagridListener
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $user = $this->securityFacade->getLoggedUser();

        if ($user instanceof AccountUser &&
            $user->getAccount() &&
            $this->securityFacade->isGranted('orob2b_account_frontend_account_user_role_view')
        ) {
            $andWhere = 'role.account IN (' . $user->getAccount()->getId() . ') or role.account IS NULL';
            $config->offsetAddToArrayByPath('[source][query][where][and]', [$andWhere]);
        } else {
            $config->offsetAddToArrayByPath('[source][query][where][and]', ['1=0']);
        }
    }
}
