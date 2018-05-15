<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BaseCouponType extends AbstractType
{
    const NAME = 'oro_promotion_base_coupon_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'promotion',
                PromotionSelectType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.promotion.label',
                    'autocomplete_alias' => 'oro_promotion_use_coupons',
                    'grid_name' => 'promotion-for-coupons-select-grid',
                ]
            )
            ->add(
                'enabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.enabled.label',
                ]
            )
            ->add(
                'usesPerCoupon',
                IntegerType::class,
                [
                    'required' => false,
                    'tooltip' => 'oro.promotion.coupon.form.tooltip.uses_per_coupon',
                    'label' => 'oro.promotion.coupon.uses_per_coupon.label',
                ]
            )
            ->add(
                'usesPerPerson',
                IntegerType::class,
                [
                    'required' => false,
                    'tooltip' => 'oro.promotion.coupon.form.tooltip.uses_per_person',
                    'label' => 'oro.promotion.coupon.uses_per_person.label',
                ]
            )
            ->add(
                'validFrom',
                OroDateTimeType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.valid_from.label',
                ]
            )
            ->add(
                'validUntil',
                OroDateTimeType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.valid_until.label',
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
