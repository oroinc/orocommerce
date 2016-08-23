<?php

namespace Oro\Bundle\AccountBundle\Form\Type\Frontend;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AccountBundle\Form\Type\AccountUserMultiSelectType as BaseAccountUserMultiSelectType;

class AccountUserMultiSelectType extends BaseAccountUserMultiSelectType
{
    const NAME = 'orob2b_account_frontend_account_user_multiselect';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_account_frontend_account_user',
                'configs' => [
                    'route_name' => 'orob2b_frontend_autocomplete_search',
                    'multiple' => true,
                    'component' => 'autocomplete-accountuser',
                    'placeholder' => 'oro.account.accountuser.form.choose',
                ],
                'attr' => [
                    'class' => 'account-accountuser-multiselect',
                ],
            ]
        );
    }
}
