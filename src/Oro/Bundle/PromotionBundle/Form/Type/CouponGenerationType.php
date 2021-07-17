<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitSelectAutocomplete;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

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
                BusinessUnitSelectAutocomplete::class,
                [
                    'required' => true,
                    'label' => 'oro.user.owner.label',
                    'data' => $this->getCurrentBusinessUnit(),
                    'autocomplete_alias' => 'business_units_owner_search_handler',
                    'configs' => [
                        'multiple' => false,
                        'allowClear' => false,
                        'autocomplete_alias' => 'business_units_owner_search_handler',
                        'component' => 'tree-autocomplete',
                    ],
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
                'codeLength',
                IntegerType::class,
                [
                    'required' => true,
                    'label' => 'oro.promotion.coupon.generation.codeLength.label',
                    'attr' => ['class' => 'promotion-coupon-generation-preview'],
                ]
            )
            ->add(
                'codeType',
                ChoiceType::class,
                [
                    'label' => 'oro.promotion.coupon.generation.codeType.label',
                    'choices' => $this->getCodeTypes(),
                    'attr' => ['class' => 'promotion-coupon-generation-preview'],
                ]
            )
            ->add(
                'codePrefix',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.codePrefix.label',
                    'attr' => ['class' => 'promotion-coupon-generation-preview'],
                ]
            )
            ->add(
                'codeSuffix',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.codeSuffix.label',
                    'attr' => ['class' => 'promotion-coupon-generation-preview'],
                ]
            )
            ->add(
                'dashesSequence',
                DashesSequenceType::class,
                [
                    'required' => false,
                    'label' => 'oro.promotion.coupon.generation.dashesSequence.prefix.label',
                    'attr' => ['class' => 'dashesSequence-coupon-preview promotion-coupon-generation-preview'],
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CouponGenerationOptions::class,
                'constraints' => [new Valid()],
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

    /**
     * @return array
     */
    protected function getCodeTypes()
    {
        return [
            'oro.promotion.coupon.generation.codeType.numeric.label' =>
                CouponGenerationOptions::NUMERIC_CODE_TYPE,
            'oro.promotion.coupon.generation.codeType.alphanumeric.label' =>
                CouponGenerationOptions::ALPHANUMERIC_CODE_TYPE,
            'oro.promotion.coupon.generation.codeType.alphabetic.label' =>
                CouponGenerationOptions::ALPHABETIC_CODE_TYPE,
        ];
    }
}
