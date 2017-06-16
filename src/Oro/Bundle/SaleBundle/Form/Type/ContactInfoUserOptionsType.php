<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\SaleBundle\Provider\OptionProviderWithDefaultValueInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactInfoUserOptionsType extends AbstractType
{
    const NAME = 'oro_sale_contact_info_user_option';

    /**
     * @var OptionProviderWithDefaultValueInterface
     */
    protected $optionsProvider;

    /**
     * @param OptionProviderWithDefaultValueInterface $optionsProvider
     */
    public function __construct(OptionProviderWithDefaultValueInterface $optionsProvider)
    {
        $this->optionsProvider = $optionsProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => array_flip($this->optionsProvider->getOptions()),
            'multiple' => false,
        ]);

        $resolver->setNormalizer('choice_label', function () {
            return function ($optionValue) {
                $label = sprintf('oro.sale.contact_info_user_options.type.%s.label', $optionValue);

                return $label;
            };
        });
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $options = $event->getData();
            if (empty($options)) {
                $options = $this->optionsProvider->getDefaultOption();
                $event->setData($options);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
