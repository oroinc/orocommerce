<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerGroupSelectTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return CustomerGroupSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_customer_group',
                'create_form_route' => 'oro_customer_customer_group_create',
                'configs' => [
                    'placeholder' => 'oro.customer.customergroup.form.choose'
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
