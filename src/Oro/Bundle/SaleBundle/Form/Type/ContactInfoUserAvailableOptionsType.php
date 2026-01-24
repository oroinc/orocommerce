<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\SaleBundle\Provider\OptionsProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting available user contact information options.
 *
 * Provides a multi-select choice field for choosing which contact information options
 * should be available to users. Defaults to all available options if none are selected.
 */
class ContactInfoUserAvailableOptionsType extends AbstractType
{
    const NAME = 'oro_sale_contact_info_user_available_option';

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
        $choices = $this->optionsProvider->getOptions();
        $resolver->setDefaults([
            'choices' => array_combine($choices, $choices),
            'multiple' => true,
        ]);

        $resolver->setNormalizer('choice_label', function () {
            return function ($optionValue) {
                return sprintf('oro.sale.available_user_options.type.%s.label', $optionValue);
            };
        });
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $options = $event->getData();
            if (empty($options)) {
                $options = $this->optionsProvider->getOptions();
                $event->setData($options);
            }
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
