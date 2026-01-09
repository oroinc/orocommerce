<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\SaleBundle\Provider\OptionsProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting the source of contact information.
 *
 * Provides a choice field for selecting where contact information should be sourced from,
 * such as customer, customer user, or pre-configured system values.
 */
class ContactInfoSourceOptionsType extends AbstractType
{
    public const NAME = 'oro_sale_contact_info_customer_option';

    /**
     * @var OptionsProviderInterface
     */
    protected $optionsProvider;

    public function __construct(OptionsProviderInterface $optionsProvider)
    {
        $this->optionsProvider = $optionsProvider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $options = $this->optionsProvider->getOptions();
        $resolver->setDefaults([
            'choices' => array_combine($options, $options),
        ]);

        $resolver->setNormalizer('choice_label', function () {
            return function ($optionValue) {
                return sprintf('oro.sale.available_customer_options.type.%s.label', $optionValue);
            };
        });
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
