<?php

namespace Oro\Bundle\MoneyOrderBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Form type for configuring Money Order payment method settings.
 *
 * This form type defines the fields required to configure a Money Order payment method,
 * including localized labels, short labels, payment recipient information (pay to), and
 * shipping address details (send to). It is used in the admin interface to create and edit
 * Money Order payment channel configurations.
 */
class MoneyOrderSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_money_order_settings';

    /**
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'labels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.money_order.settings.labels.label',
                    'required' => true,
                    'entry_options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'shortLabels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.money_order.settings.short_labels.label',
                    'required' => true,
                    'entry_options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'payTo',
                TextType::class,
                [
                    'label' => 'oro.money_order.settings.pay_to.label',
                    'required' => true,
                ]
            )
            ->add(
                'sendTo',
                TextareaType::class,
                [
                    'label' => 'oro.money_order.settings.send_to.label',
                    'required' => true,
                ]
            );
    }

    /**
     * @throws AccessException
     */
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => MoneyOrderSettings::class,
            ]
        );
    }

    /**
     * @return string
     */
    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }
}
