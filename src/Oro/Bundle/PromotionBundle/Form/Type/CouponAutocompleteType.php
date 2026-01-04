<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Coupon select.
 */
class CouponAutocompleteType extends AbstractType
{
    public const NAME = 'oro_promotion_coupon_autocomplete';

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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_coupon',
                'grid_name' => 'promotion-coupons-select-grid',
                'label' => 'oro.promotion.coupon.entity_label',
                'configs' => [
                    'placeholder' => 'oro.promotion.coupon.autocomplete.placeholder',
                    'result_template_twig' => '@OroPromotion/Coupon/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@OroPromotion/Coupon/Autocomplete/selection.html.twig',
                ]
            ]
        );
    }
}
