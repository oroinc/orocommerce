<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing collections of order line items.
 *
 * Specialized collection form type configured for handling order line items with appropriate defaults
 * for prototype naming, error handling, and form behavior specific to line item collections.
 */
class OrderLineItemsCollectionType extends AbstractType
{
    public const NAME = 'oro_order_line_items_collection';

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => OrderLineItemType::class,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
                'prototype_name' => '__nameorderlineitem__',
                'prototype' => true,
                'handle_primary' => false
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
}
