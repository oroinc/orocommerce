<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

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
