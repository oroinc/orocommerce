<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;

class AccountUserMultiSelectType extends AbstractType
{
    const NAME = 'oro_account_account_user_multiselect';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return UserMultiSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_account_account_user',
                'configs' => [
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
