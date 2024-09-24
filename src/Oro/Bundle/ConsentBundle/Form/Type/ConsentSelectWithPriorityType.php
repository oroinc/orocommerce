<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Consent select type with ordering
 */
class ConsentSelectWithPriorityType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                ConsentConfigConverter::CONSENT_KEY,
                ConsentSelectType::class,
                [
                    'empty_data' => null,
                    'required' => true,
                    'label' => 'oro.consent.entity_label',
                    'create_enabled' => false,
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'sortable' => true,
            'sortable_property_path' => ConsentConfigConverter::SORT_ORDER_KEY,
            'data_class' => Consent::class,
            'allow_extra_fields' => true
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_consent_select_with_priority';
    }
}
