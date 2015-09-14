<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

class AccountUserRoleFrontendType extends AbstractAccountUserRoleType
{
    const NAME = 'orob2b_account_account_user_role_frontend';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
