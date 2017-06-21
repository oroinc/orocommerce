<?php

namespace Oro\Bundle\CouponBundle\Form\Type;

use Oro\Bundle\CouponBundle\Entity\Coupon;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BaseCouponType extends AbstractType
{
    const NAME = 'base_coupon_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'usesPerCoupon',
            'integer',
            [
                'required' => false,
                'tooltip' => 'oro.coupon.form.tooltip.uses_per_coupon',
                'label' => 'oro.coupon.uses_per_coupon.label',
            ]
        )->add(
            'usesPerUser',
            'integer',
            [
                'required' => false,
                'tooltip' => 'oro.coupon.form.tooltip.uses_per_user',
                'label' => 'oro.coupon.uses_per_user.label',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Coupon::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
