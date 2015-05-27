<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

class PriceListType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', ['required' => true, 'label' => 'orob2b.pricing.pricelist.name.label'])
            ->add(
                'currencies',
                'oro_currency_selection',
                [
                    'multiple' => true,
                    'required' => true,
                    'label' => 'orob2b.pricing.pricelist.currencies.label'
                ]
            )
            ->add(
                'appendCustomers',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2BCustomerBundle:Customer',
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeCustomers',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2BCustomerBundle:Customer',
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'appendCustomerGroups',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2BCustomerBundle:CustomerGroup',
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeCustomerGroups',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2BCustomerBundle:CustomerGroup',
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'appendWebsites',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2BWebsiteBundle:Website',
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeWebsites',
                EntityIdentifierType::NAME,
                [
                    'class' => 'OroB2BWebsiteBundle:Website',
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            );
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\PricingBundle\Entity\PriceList'
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
