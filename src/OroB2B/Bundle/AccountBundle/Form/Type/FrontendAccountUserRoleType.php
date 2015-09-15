<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

class FrontendAccountUserRoleType extends AbstractAccountUserRoleType
{
    const NAME = 'orob2b_account_frontend_account_user_role';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
