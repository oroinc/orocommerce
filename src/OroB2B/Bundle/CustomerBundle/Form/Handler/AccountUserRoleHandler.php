<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Handler;

use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;

use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserRoleType;

class AccountUserRoleHandler extends AclRoleHandler
{
    /**
     * {@inheritdoc}
     */
    protected function createRoleFormInstance(AbstractRole $role, array $privilegeConfig)
    {
        return $this->formFactory->create(
            AccountUserRoleType::NAME,
            $role,
            ['privilege_config' => $privilegeConfig]
        );
    }
}
