<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductImageTypesTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Responsible for product image types.
 */
class ProductImageTypesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ProductImageTypesTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('image_types')
            ->setAllowedTypes('image_types', 'array')
            ->setDefaults([
                'multiple' => true,
                'choices' => function (Options $options) {
                    $typesKeys = array_map(function (ThemeImageType $imageType) {
                        return $imageType->getName();
                    }, $options['image_types']);

                    return array_combine($typesKeys, $typesKeys);
                }
            ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
