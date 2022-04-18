<?php

namespace Oro\Bundle\FixedProductShippingBundle\Form\Type;

use Oro\Bundle\FixedProductShippingBundle\Entity\FixedProductSettings;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for fixed product integration settings.
 */
class FixedProductSettingsType extends AbstractType
{
    public const BLOCK_PREFIX = 'oro_flat_rate_settings';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('labels', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.fixed_product.settings.labels.label',
                'tooltip'  => 'oro.fixed_product.settings.labels.tooltip',
                'required' => true,
                'entry_options'  => [
                    'constraints' => [new NotBlank()],
                ]
            ]);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FixedProductSettings::class
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }
}
