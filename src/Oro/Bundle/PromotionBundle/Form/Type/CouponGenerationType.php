<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitSelectAutocomplete;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Form type that used to receive options for coupon generation from User.
 */
class CouponGenerationType extends AbstractType
{
    const NAME = 'oro_promotion_coupon_generation_type';

    /**
     * @var TokenAccessorInterface
     */
    protected $tokenAccessor;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'owner',
                BusinessUnitSelectAutocomplete::NAME,
                [
                    'required' => true,
                    'label' => 'oro.user.owner.label',
                    'data' => $this->getCurrentBusinessUnit(),
                    'autocomplete_alias' => 'business_units_owner_search_handler',
                    'empty_value' => 'oro.business_unit.form.choose_business_user',
                    'configs' => [
                        'multiple' => false,
                        'allowClear' => false,
                        'autocomplete_alias' => 'business_units_owner_search_handler',
                        'component' => 'tree-autocomplete',
                    ]
                ]
            )
            ->add(
                'couponQuantity',
                IntegerType::class,
                ['required' => true, 'label' => 'oro.promotion.coupon.generation.couponQuantity.label',]
            )
            ->add(
                'promotion',
                PromotionSelectType::NAME,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.promotion.label',
                    'autocomplete_alias' => 'oro_promotion_use_coupons',
                ]
            )
            ->add(
                'usesPerCoupon',
                IntegerType::class,
                [
                    'required' => false,
                    'tooltip' => 'oro.promotion.coupon.form.tooltip.uses_per_coupon',
                    'label' => 'oro.promotion.coupon.uses_per_coupon.label',
                    'data' => 1
                ]
            )
            ->add(
                'usesPerUser',
                IntegerType::class,
                [
                    'required' => false,
                    'tooltip' => 'oro.promotion.coupon.form.tooltip.uses_per_user',
                    'label' => 'oro.promotion.coupon.uses_per_user.label',
                    'data' => 1
                ]
            )
            ->add(
                'validUntil',
                OroDateTimeType::NAME,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.valid_until.label',
                ]
            )
            ->add(
                'codeLength',
                IntegerType::class,
                [
                    'required' => true,
                    'label' => 'oro.promotion.coupon.generation.codeLength.label',
                    'data' => 12,
                    'attr' => ['class' => 'promotion-coupon-generation-preview']
                ]
            )
            ->add(
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
                    'attr' => ['class' => 'promotion-coupon-generation-preview']
                ]
            )
            ->add(
                'codePrefix',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.codePrefix.label',
                    'attr' => ['class' => 'promotion-coupon-generation-preview']
                ]
            )
            ->add(
                'codeSuffix',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.codeSuffix.label',
                    'attr' => ['class' => 'promotion-coupon-generation-preview']
                ]
            )
            ->add(
                'dashesSequence',
                DashesSequenceType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.dashesSequence.prefix.label',
                    'attr' => ['class' => 'input-small promotion-coupon-generation-preview'],
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return $this->getName();
    }

    /**
     * @return BusinessUnit|null
     */
    protected function getCurrentBusinessUnit()
    {
        $currentOrganization = $this->tokenAccessor->getOrganization();
        $user = $this->tokenAccessor->getUser();
        if (!$user instanceof User || !$currentOrganization instanceof Organization) {
            return null;
        }

        return $user->getBusinessUnits()
            ->filter(function (BusinessUnit $businessUnit) use ($currentOrganization) {
                return $businessUnit->getOrganization()->getId() === $currentOrganization->getId();
            })
            ->first();
    }
}
