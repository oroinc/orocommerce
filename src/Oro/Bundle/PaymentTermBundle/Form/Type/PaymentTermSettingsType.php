<?php

namespace Oro\Bundle\PaymentTermBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Form type for configuring payment term settings.
 *
 * This form type handles the configuration of payment term labels and short labels with localization support.
 * It is used in the payment term integration settings to allow administrators to define
 * how payment terms are displayed in the system.
 */
class PaymentTermSettingsType extends AbstractType
{
    public const BLOCK_PREFIX = 'oro_payment_term_settings';

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
                    'label' => 'oro.paymentterm.settings.labels.label',
                    'required' => true,
                    'entry_options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'shortLabels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.paymentterm.settings.short_labels.label',
                    'required' => true,
                    'entry_options' => ['constraints' => [new NotBlank()]],
                ]
            );
    }

    /**
     * @throws AccessException
     */
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PaymentTermSettings::class,
        ]);
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
