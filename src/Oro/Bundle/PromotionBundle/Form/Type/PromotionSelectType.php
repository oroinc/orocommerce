<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting a promotion.
 *
 * Extends {@see OroEntitySelectOrCreateInlineType} to provide autocomplete selection
 * of promotions with support for inline creation.
 */
class PromotionSelectType extends AbstractType
{
    const NAME = 'oro_promotion_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => PromotionType::class,
                'create_form_route' => 'oro_promotion_create',
                'create_enabled' => false,
                'configs' => [
                    'placeholder' => 'oro.promotion.form.choose',
                ]
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

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }
}
