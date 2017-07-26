<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CouponGenerationType extends AbstractType
{
    const NAME = 'oro_promotion_coupon_generation_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'owner',
                'oro_type_business_unit_select_autocomplete',
                [
                    'required' => false,
                    'label' => 'oro.user.owner.label',
                    'autocomplete_alias' => 'business_units_owner_search_handler',
                    'empty_value' => 'oro.business_unit.form.choose_business_user',
                    'configs' => [
                        'multiple' => false,
                        'allowClear'  => false,
                        'autocomplete_alias' => 'business_units_owner_search_handler',
                        'component'   => 'tree-autocomplete',
                    ]
                ]
            )
            ->add(
                'couponQuantity',
                IntegerType::class,
                [
                    'required' => true,
                    'label' => 'oro.promotion.coupon.generation.couponQuantity.label',
                ]
            )
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
                    'label' => 'oro.promotion.coupon.uses_per_coupon.label',
                    'data' => 1
                ]
            )
            ->add(
                'usesPerUser',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.uses_per_user.label',
                    'data' => 1
                ]
            )
            ->add(
                'codeLength',
                IntegerType::class,
                [
                    'required' => true,
                    'label' => 'oro.promotion.coupon.generation.codeLength.label',
                    'data' => 12
                ]
            )->add(
                'codeType',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.codeType.label',
                ]
            )->add(
                'codeType',
                ChoiceType::class,
                [
                    'choices' => [
                        CouponGenerationOptions::NUMERIC_CODE_TYPE =>
                            'oro.promotion.coupon.generation.codeType.numeric.label',
                        CouponGenerationOptions::ALPHANUMERIC_CODE_TYPE =>
                            'oro.promotion.coupon.generation.codeType.alphanumeric.label',
                        CouponGenerationOptions::ALPHABETIC_CODE_TYPE =>
                            'oro.promotion.coupon.generation.codeType.alphabetic.label',
                    ],
                ]
            )->add(
                'codePrefix',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.codePrefix.label',
                ]
            )->add(
                'codeSuffix',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.codeSuffix.label',
                ]
            )->add(
                'dashesSequence',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.dashesSequence.label',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CouponGenerationOptions::class,
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
