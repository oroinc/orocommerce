<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\FieldsOptionsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type to select entity field which should be used for Checkout line items grouping.
 */
class AvailableGroupingFieldsConfigType extends AbstractType
{
    private const NAME = 'oro_checkout_available_grouping_fields';

    private FieldsOptionsProvider $optionsProvider;

    public function __construct(FieldsOptionsProvider $optionsProvider)
    {
        $this->optionsProvider = $optionsProvider;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'label' => false,
                'choices' => $this->optionsProvider->getAvailableFieldsForGroupingFormOptions(),
            ]);
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
