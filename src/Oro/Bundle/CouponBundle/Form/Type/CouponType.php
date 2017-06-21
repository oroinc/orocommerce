<?php

namespace Oro\Bundle\CouponBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CouponType extends AbstractType
{
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
                'tooltip' => 'oro.coupon.form.tooltip.coupon_code',
                'label' => 'oro.coupon.code.label',
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
}
