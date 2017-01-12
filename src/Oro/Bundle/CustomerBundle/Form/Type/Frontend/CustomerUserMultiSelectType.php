<?php

namespace Oro\Bundle\CustomerBundle\Form\Type\Frontend;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserMultiSelectType as BaseCustomerUserMultiSelectType;

class CustomerUserMultiSelectType extends BaseCustomerUserMultiSelectType
{
    const NAME = 'oro_customer_frontend_customer_user_multiselect';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_customer_frontend_customer_user',
                'configs' => [
                    'route_name' => 'oro_frontend_autocomplete_search',
                    'multiple' => true,
                    'component' => 'autocomplete-customeruser',
                    'placeholder' => 'oro.customer.customeruser.form.choose',
                ],
                'attr' => [
                    'class' => 'customer-customeruser-multiselect',
                ],
            ]
        );
    }
}
