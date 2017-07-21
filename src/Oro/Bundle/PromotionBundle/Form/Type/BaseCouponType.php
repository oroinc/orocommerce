<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\PromotionBundle\Entity\Coupon;

use Symfony\Component\Form\AbstractType;
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
                PromotionSelectType::NAME,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.promotion.label',
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
                'usesPerUser',
                IntegerType::class,
                [
                    'required' => false,
                    'tooltip' => 'oro.promotion.coupon.form.tooltip.uses_per_user',
                    'label' => 'oro.promotion.coupon.uses_per_user.label',
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
