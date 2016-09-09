<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class TaxProviderType extends AbstractType
{
    const NAME = 'oro_tax_provider_type';

    /**
     * @var TaxProviderRegistry
     */
    protected $registry;

    /**
     * @param TaxProviderRegistry $registry
     */
    public function __construct(TaxProviderRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choicesRaw = $this->registry->getProviders();

        $choices = [];

        foreach ($choicesRaw as $choiceRaw) {
            $choices[$choiceRaw->getName()] = $choiceRaw->getLabel();
        }

        $resolver->setDefaults(['choices' => $choices]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
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
