<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;

/**
 * Form type for line item discount options.
 *
 * Provides form fields for configuring line item discounts including product unit selection,
 * application strategy (per-item or total), and optional quantity limits.
 */
class LineItemDiscountOptionsType extends AbstractType
{
    const NAME = 'oro_promotion_line_item_discount_options';

    const APPLY_TO_FIELD_CHOICES = [
        LineItemsDiscount::EACH_ITEM,
        LineItemsDiscount::LINE_ITEMS_TOTAL
    ];

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE,
            ProductUnitsType::class,
            [
                'label' => 'oro.discount_options.line_item_type.product_unit.label'
            ]
        )
        ->add(
            LineItemsDiscount::APPLY_TO,
            ChoiceType::class,
            [
                'choices' => $options['apply_to_choices'],
                'label' => 'oro.discount_options.line_item_type.apply_to.label',
                'placeholder' => false,
                'required' => false,
            ]
        )
        ->add(
            LineItemsDiscount::MAXIMUM_QTY,
            NumberType::class,
            [
                'label' => 'oro.discount_options.line_item_type.maximum_qty.label',
                'tooltip' => 'oro.discount_options.line_item_type.maximum_qty.tooltip',
                'constraints' => [new GreaterThan(['value' => 0])],
                'required' => false,
            ]
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('apply_to_choices', $this->getApplyToChoices());
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
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
            $choices['oro.discount_options.line_item_type.apply_to.choices.' . $item] = $item;
        }

        return $choices;
    }
}
