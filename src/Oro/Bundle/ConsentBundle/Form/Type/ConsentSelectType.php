<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Consent select
 */
class ConsentSelectType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_consent_list',
                'entity_class' => Consent::class,
                'grid_name' => 'consents-grid',
                'create_form_route' => 'oro_consent_create',
                'configs' => [
                    'placeholder' => 'oro.consent.form.choose_consent'
                ],
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_consent_select';
    }
}
