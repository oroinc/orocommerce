<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\ShippingBundle\Entity\MultiShippingSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Multi shipping settings
 */
class MultiShippingSettingsType extends AbstractType
{
    public const BLOCK_PREFIX = 'oro_multi_shipping_settings';

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MultiShippingSettings::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }
}
