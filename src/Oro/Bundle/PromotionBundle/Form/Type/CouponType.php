<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CouponType extends AbstractType
{
    const NAME = 'oro_promotion_coupon_type';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'code',
            TextType::class,
            [
                'required' => true,
                'tooltip' => 'oro.promotion.coupon.form.tooltip.coupon_code',
                'label' => 'oro.promotion.coupon.code.label',
            ]
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Coupon::class,
                'validation_groups' => ['Default', 'all_coupon_fields'],
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return BaseCouponType::class;
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
}
