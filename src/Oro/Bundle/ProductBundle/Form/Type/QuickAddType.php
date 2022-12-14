<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Validator\Constraints\QuickAddComponentProcessor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Count;

/**
 * Form type that represents quick add form.
 */
class QuickAddType extends AbstractType
{
    public const NAME = 'oro_product_quick_add';

    public const PRODUCTS_FIELD_NAME = 'products';
    public const COMPONENT_FIELD_NAME = 'component';
    public const ADDITIONAL_FIELD_NAME = 'additional';
    public const TRANSITION_FIELD_NAME = 'transition';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                self::PRODUCTS_FIELD_NAME,
                QuickAddRowCollectionType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [new Count(['min' => 1, 'minMessage' => 'oro.product.at_least_one_item'])],
                    'add_label' => 'oro.product.form.add_row',
                ]
            )
            ->add(
                self::COMPONENT_FIELD_NAME,
                HiddenType::class,
                ['constraints' => [new QuickAddComponentProcessor()]]
            )
            ->add(
                self::ADDITIONAL_FIELD_NAME,
                HiddenType::class
            )
            ->add(
                self::TRANSITION_FIELD_NAME,
                HiddenType::class
            );
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_quick_add';
    }
}
