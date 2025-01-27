<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Collection of OrderLimitType form type for minimum and maximum order amount settings
 */
class OrderLimitCollectionType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => OrderLimitType::class,
                'allow_add' => false,
                'allow_delete' => false,
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_checkout_order_limit_collection';
    }
}
