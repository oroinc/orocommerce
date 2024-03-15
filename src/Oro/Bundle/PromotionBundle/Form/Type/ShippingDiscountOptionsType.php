<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 *  Form type for Shipping Discount Options.
 */
class ShippingDiscountOptionsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                ShippingDiscount::SHIPPING_OPTIONS,
                ShippingMethodTypesChoiceType::class,
                [
                    'label' => 'oro.discount_options.shipping_type.shipping_method.label',
                    'constraints' => [new NotBlank()]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_promotion_shipping_discount_options';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return DiscountOptionsType::class;
    }
}
