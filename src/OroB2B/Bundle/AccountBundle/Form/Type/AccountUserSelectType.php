<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class AccountUserSelectType extends AbstractType
{
    const NAME = 'orob2b_account_account_user_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_account_account_user',
                'create_form_route' => 'orob2b_account_account_user_create',
                'configs' => [
                    'component' => 'autocomplete-accountuser',
                    'placeholder' => 'orob2b.account.accountuser.form.choose',
                ],
                'attr' => [
                    'class' => 'account-accountuser-select',
                ],
            ]
        );
    }

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
    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::NAME;
    }
}
