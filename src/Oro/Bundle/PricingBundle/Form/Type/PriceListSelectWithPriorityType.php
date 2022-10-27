<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PriceListSelectWithPriorityType extends AbstractType
{
    const NAME = 'oro_pricing_price_list_select_with_priority';
    const PRICE_LIST_FIELD = 'priceList';
    const SORT_ORDER_FIELD = 'sort_order';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::PRICE_LIST_FIELD,
                PriceListSelectType::class,
                [
                    'empty_data' => null,
                    'required' => true,
                    'label' => 'oro.pricing.pricelist.entity_label',
                    'create_enabled' => false,
                    'constraints' => [new NotBlank()],
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'sortable' => true,
            'sortable_property_path' => self::SORT_ORDER_FIELD,
            'data_class' => BasePriceListRelation::class
        ]);
    }
}
