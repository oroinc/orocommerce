<?php

namespace OroB2B\Bundle\AccountBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

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
        if ($user instanceof AccountUser) {
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
    public function getAccountUserRolePermission(ResultRecordInterface $record)
    {
        $isGranted = true;
        $delete = true;
        if ($record->getValue('isRolePredefined')) {
            $isGranted = $this->securityFacade->isGranted('orob2b_account_frontend_account_user_role_create');
            $delete = false;
        }

        return [
            'update' => $isGranted,
            'delete' => $delete
        ];
    }
}
