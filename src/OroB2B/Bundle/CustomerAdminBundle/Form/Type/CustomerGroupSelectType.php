<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CustomerGroupSelectType extends AbstractType
{
    const NAME = 'orob2b_customer_admin_customer_group_select';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_customer_admin_customer_group',
                'create_form_route' => 'orob2b_customer_admin_group_create',
                'configs' => [
                    'placeholder' => 'orob2b.customeradmin.customergroup.form.choose'
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
        return 'oro_entity_create_or_select_inline';
    }
}
