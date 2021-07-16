<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\SaleBundle\Provider\OptionsProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactInfoSourceOptionsType extends AbstractType
{
    const NAME = 'oro_sale_contact_info_customer_option';

    /**
     * @var OptionsProviderInterface
     */
    protected $optionsProvider;

    public function __construct(OptionsProviderInterface $optionsProvider)
    {
        $this->optionsProvider = $optionsProvider;
    }

    /**
     * {@inheritDoc}
     */
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
        return ChoiceType::class;
    }
}
