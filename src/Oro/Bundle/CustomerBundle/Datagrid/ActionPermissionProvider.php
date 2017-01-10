<?php

namespace Oro\Bundle\CustomerBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class ActionPermissionProvider
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getUserPermissions(ResultRecordInterface $record)
    {
        $disabled = $enabled = $record->getValue('enabled');
        $user = $this->securityFacade->getLoggedUser();
        $delete = true;
        if ($user instanceof CustomerUser) {
            $isCurrentUser = $user->getId() == $record->getValue('id');
            $disabled = $isCurrentUser ? false : $enabled;
            $delete = !$isCurrentUser;
        }

        return [
            'enable' => !$enabled,
            'disable' => $disabled,
            'view' => true,
            'update' => true,
            'delete' => $delete
        ];
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getCustomerUserRolePermission(ResultRecordInterface $record)
    {
        $isGranted = true;
        if ($record->getValue('isRolePredefined')) {
            $isGranted = $this->securityFacade->isGranted('oro_account_frontend_customer_user_role_create');
        }

        return [
            'view' => true,
            'update' => $isGranted
        ];
    }
}
