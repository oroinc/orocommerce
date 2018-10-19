<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\PromotionBundle\Discount\BuyXGetYDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class BuyXGetYDiscountOptionsType extends AbstractType
{
    const NAME = 'oro_promotion_buy_x_get_y_discount_options';
    const APPLY_TO_FIELD_CHOICES = [
        BuyXGetYDiscount::APPLY_TO_EACH_Y,
        BuyXGetYDiscount::APPLY_TO_XY_TOTAL,
    ];

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                BuyXGetYDiscount::BUY_X,
                IntegerType::class,
                [
                    'label' => 'oro.discount_options.buy_x_get_y_type.buy_x.label',
                    'constraints' => [new Type('integer'), new NotBlank(), new GreaterThanZero()],
                ]
            )
            ->add(
                BuyXGetYDiscount::GET_Y,
                IntegerType::class,
                [
                    'label' => 'oro.discount_options.buy_x_get_y_type.get_y.label',
                    'constraints' => [new Type('integer'), new NotBlank(), new GreaterThanZero()],
                ]
            )
            ->add(
                DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE,
                ProductUnitsType::class,
                [
                    'label' => 'oro.discount_options.buy_x_get_y_type.product_unit.label'
                ]
            )
            ->add(
                BuyXGetYDiscount::DISCOUNT_APPLY_TO,
                ChoiceType::class,
                [
                    'choices' => $options['apply_to_choices'],
                    'required' => false,
                    'label' => 'oro.discount_options.buy_x_get_y_type.apply_to.label',
                    'placeholder' => false,
                ]
            )
            ->add(
                BuyXGetYDiscount::DISCOUNT_LIMIT,
                IntegerType::class,
                [
                    'label' => 'oro.discount_options.buy_x_get_y_type.limit_times.label',
                    'tooltip' => 'oro.discount_options.buy_x_get_y_type.limit_times.tooltip',
                    'required' => false,
                    'constraints' => [new Type('integer'), new GreaterThanZero()],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('apply_to_choices', $this->getApplyToChoices());
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DiscountOptionsType::class;
    }

    /**
     * @return array
     */
    private function getApplyToChoices()
    {
        $choices = [];
        foreach (self::APPLY_TO_FIELD_CHOICES as $item) {
            $choices['oro.discount_options.buy_x_get_y_type.apply_to.choices.' . $item] = $item;
        }

        return $choices;
    }
}
