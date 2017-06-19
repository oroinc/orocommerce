<?php

namespace Oro\Bundle\CouponBundle\Form\Type;

use Oro\Bundle\CouponBundle\Entity\Coupon;
use Oro\Bundle\ValidationBundle\Validator\Constraints\AlphanumericDash;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CouponType extends AbstractType
{
    const NAME = 'oro_coupon';

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
        )->add(
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
