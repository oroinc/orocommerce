<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\CustomerBundle\Form\Type\AccountGroupSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountGroupSelectTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return AccountGroupSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_account_group',
                'create_form_route' => 'oro_customer_account_group_create',
                'configs' => [
                    'placeholder' => 'oro.customer.accountgroup.form.choose'
                ]
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
