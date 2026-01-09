<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form type for configuring individual product variant fields.
 *
 * This form type provides fields for setting the priority and selection state of a variant field, allowing users
 * to configure which product attributes should be used as variant fields in configurable products.
 */
class ProductVariantFieldType extends AbstractType
{
    public const NAME = 'oro_product_variant_field';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('priority', HiddenType::class, ['empty_data' => 9999])
            ->add(
                'is_selected',
                CheckboxType::class,
                [
                    'required' => false,
                    'attr' => ['data-original-name' => $builder->getName()],
                    'label' => $options['label']
                ]
            );
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
