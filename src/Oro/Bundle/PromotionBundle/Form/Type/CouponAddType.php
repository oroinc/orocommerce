<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CouponAddType extends AbstractType
{
    const NAME = 'oro_promotion_coupon_add';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'coupon',
                CouponAutocompleteType::NAME,
                [
                    'tooltip' => 'oro.promotion.coupon.form.add_type.code.tooltip',
                    'label' => 'oro.promotion.coupon.code.label',
                    'mapped' => false,
                ]
            )
            ->add(
                'addedIds',
                EntityIdentifierType::NAME,
                [
                    'class' => Coupon::class,
                    'multiple' => true,
                    'mapped' => false,
                ]
            );
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
