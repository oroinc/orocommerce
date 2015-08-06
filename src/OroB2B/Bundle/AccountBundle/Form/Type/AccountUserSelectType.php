<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class AccountUserSelectType extends AbstractType
{
    const NAME = 'orob2b_account_account_user_select';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_account_account_user',
                'create_form_route' => 'orob2b_account_account_user_create',
                'configs' => [
                    'placeholder' => 'orob2b.account.accountuser.form.choose'
                ]
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
