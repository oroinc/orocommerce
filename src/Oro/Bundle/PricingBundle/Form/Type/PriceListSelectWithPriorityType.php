<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for selecting a price list with priority/sort order.
 *
 * Provides a form for selecting a price list and managing its priority in a collection,
 * supporting sortable price list relations.
 */
class PriceListSelectWithPriorityType extends AbstractType
{
    const NAME = 'oro_pricing_price_list_select_with_priority';
    const PRICE_LIST_FIELD = 'priceList';
    const SORT_ORDER_FIELD = 'sort_order';

    #[\Override]
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

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'sortable' => true,
            'sortable_property_path' => self::SORT_ORDER_FIELD,
            'data_class' => BasePriceListRelation::class
        ]);
    }
}
