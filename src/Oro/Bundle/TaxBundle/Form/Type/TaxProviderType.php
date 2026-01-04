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
    public const NAME = 'oro_tax_provider_type';

    /**
     * @var TaxProviderRegistry
     */
    protected $registry;

    public function __construct(TaxProviderRegistry $registry)
    {
        $this->registry = $registry;
    }

    #[\Override]
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

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
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
}
