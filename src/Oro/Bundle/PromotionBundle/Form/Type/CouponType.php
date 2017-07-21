<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CouponType extends AbstractType
{
    const NAME = 'oro_promotion_coupon_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'code',
            'text',
            [
                'required' => true,
                'tooltip' => 'oro.promotion.coupon.form.tooltip.coupon_code',
                'label' => 'oro.promotion.coupon.code.label',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return BaseCouponType::class;
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
        return self::NAME;
    }
}
