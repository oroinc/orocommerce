<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Oro\Bundle\UserBundle\Entity\AbstractRole;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountAclAccessLevelTextType;

class AccountUserRoleViewHandler extends AccountUserRoleUpdateHandler
{
    /**
     * {@inheritdoc}
     */
    public function process(AbstractRole $role)
    {
        $this->setRolePrivileges($role);
    }

    /**
     * {@inheritdoc}
     */
    public function createForm(AbstractRole $role)
    {
        foreach ($this->privilegeConfig as $configName => $config) {
            $this->privilegeConfig[$configName]['field_type'] = AccountAclAccessLevelTextType::NAME;
        }

        return parent::createForm($role);
    }
}
