<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerType extends AbstractType
{
    const NAME = 'orob2b_customer_admin_customer_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', ['label' => 'orob2b.customeradmin.customer.name.label'])
            ->add(
                'group',
                'orob2b_customer_admin_customer_group_select',
                [
                    'label' => 'orob2b.customeradmin.customer.group.label',
                    'required' => false
                ]
            )
            ->add(
                'parent',
                'orob2b_customer_admin_customer_parent_select',
                [
                    'label' => 'orob2b.customeradmin.customer.parent.label',
                    'required' => false
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
}
