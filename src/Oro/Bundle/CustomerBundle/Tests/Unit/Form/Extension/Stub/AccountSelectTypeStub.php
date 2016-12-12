<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\CustomerBundle\Form\Type\AccountSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountSelectTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return AccountSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_customer_account',
                'create_form_route' => 'oro_customer_account_create',
                'configs' => [
                    'placeholder' => 'oro.customer.account.form.choose',
                ],
                'attr' => [
                    'class' => 'account-account-select',
                ],
            ]
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
