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
                'entity',
                [
                    'label' => 'orob2b.customeradmin.customer.group.label',
                    'class' => 'OroB2B\Bundle\CustomerAdminBundle\Entity\CustomerGroup',
                    'property' => 'name',
                    'required' => false
                ]
            )
            ->add(
                'parent',
                'entity',
                [
                    'label' => 'orob2b.customeradmin.customer.parent.label',
                    'class' => 'OroB2B\Bundle\CustomerAdminBundle\Entity\Customer',
                    'property' => 'name',
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
