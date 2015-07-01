<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;

class CustomerType extends AbstractType
{
    const NAME = 'orob2b_customer_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', ['label' => 'orob2b.customer.name.label'])
            ->add(
                'group',
                CustomerGroupSelectType::NAME,
                [
                    'label' => 'orob2b.customer.group.label',
                    'required' => false
                ]
            )
            ->add(
                'parent',
                ParentCustomerSelectType::NAME,
                [
                    'label' => 'orob2b.customer.parent.label',
                    'required' => false
                ]
            )
            ->add(
                'internal_rating',
                'oro_enum_select',
                [
                    'label'     => 'orob2b.customer.internal_rating.label',
                    'enum_code' => Customer::INTERNAL_RATING_CODE,
                    'configs' => [
                        'allowClear' => false,
                    ],
                    'required' => false
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
