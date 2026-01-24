<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing a collection of tax base exclusions.
 *
 * Tax base exclusions define which components (such as shipping costs or handling fees) should be excluded
 * from the taxable amount when calculating taxes. This collection type allows administrators to configure
 * multiple exclusions that will be applied during tax calculations.
 */
class TaxBaseExclusionCollectionType extends AbstractType
{
    const NAME = 'oro_tax_base_exclusion_collection';

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => TaxBaseExclusionType::class,
                'show_form_when_empty' => false
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
