<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class CustomerGroupType extends AbstractType
{
    const NAME = 'orob2b_customer_group_type';

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
                    'label' => 'orob2b.customer.customergroup.name.label',
                    'required' => true
                ]
            )
            ->add(
                'appendCustomers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroB2B\Bundle\CustomerBundle\Entity\Customer',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            )
            ->add(
                'removeCustomers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroB2B\Bundle\CustomerBundle\Entity\Customer',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            )
            ->add(
                'priceList',
                PriceListSelectType::NAME,
                [
                    'label' => 'orob2b.customer.customergroup.price_list.label',
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
