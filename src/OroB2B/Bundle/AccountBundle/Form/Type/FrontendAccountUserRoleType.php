<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'access_level_route' => 'orob2b_account_frontend_acl_access_levels',
        ]);
    }
}
