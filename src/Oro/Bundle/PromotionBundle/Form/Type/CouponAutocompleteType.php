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
    const NAME = 'oro_promotion_coupon_autocomplete';

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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::class;
    }

    /**
     * {@inheritdoc}
     */
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
