<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerGroupType extends AbstractType
{
    const NAME = 'orob2b_customer_admin_customer_group_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                'text',
                [
                    'label' => 'orob2b.customeradmin.customergroup.name.label',
                    'required' => true
                ]
            )
            ->add(
                'appendCustomers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroB2B\Bundle\CustomerAdminBundle\Entity\Customer',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            )
            ->add(
                'removeCustomers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroB2B\Bundle\CustomerAdminBundle\Entity\Customer',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
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
