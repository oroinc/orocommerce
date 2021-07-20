<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select a tax provider from the list of applicable tax providers.
 */
class TaxProviderType extends AbstractType
{
    const NAME = 'oro_tax_provider_type';

    /**
     * @var TaxProviderRegistry
     */
    protected $registry;

    public function __construct(TaxProviderRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        $providers = $this->registry->getProviders();
        foreach ($providers as $name => $provider) {
            $choices[$provider->getLabel()] = $name;
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
