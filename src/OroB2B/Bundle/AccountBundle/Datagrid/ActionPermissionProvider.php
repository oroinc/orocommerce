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
        $enabled = $record->getValue('enabled');
        $user = $this->securityFacade->getLoggedUser();
        $disable = $enabled;
        $delete = true;
        if ($user instanceof AccountUser) {
            $thatUser = $user->getId() == $record->getValue('id');
            $disable = $thatUser ? false : $enabled;
            $delete = !$thatUser;
        }

        return [
            'enable' => !$enabled,
            'disable' => $disable,
            'view' => true,
            'update' => true,
            'delete' => $delete
        ];
    }
}
