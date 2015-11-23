<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PriceListSelectWithPriorityType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_select_with_priority';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'priceList',
                PriceListSelectType::NAME,
                [
                    'required' => false,
                    'label' => 'orob2b.pricing.pricelist.entity_label',
                    'create_enabled' => false,
                    'constraints' => [new NotBlank()]
                ]
            )
            ->add('priority', 'text', [
                'required' => false,
                'constraints' => [new NotBlank()]
            ])
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
