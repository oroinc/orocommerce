<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Component\WebCatalog\Form\PageVariantType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryPageVariantType extends AbstractType
{
    const NAME = 'oro_catalog_category_page_variant';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'excludeSubcategories',
                OroChoiceType::class,
                [
                    'label' => 'oro.catalog.subcategory.form.exclude_subcategories.label',
                    // TODO: remove 'choices_as_values' option below in scope of BAP-15236
                    'choices_as_values' => true,
                    'choices' => [
                        'oro.catalog.subcategory.form.exclude_subcategories.include.label' => 0,
                        'oro.catalog.subcategory.form.exclude_subcategories.exclude.label' => 1,
                    ],
                    'required' => true,
                    'tooltip' => 'oro.catalog.subcategory.form.exclude_subcategories.tooltip',
                ]
            )
            ->add(
                'categoryPageCategory',
                CategoryTreeType::class,
                [
                    'label' => 'oro.catalog.category.entity_label',
                    'required' => true,
                    'constraints' => [new NotBlank()]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return PageVariantType::class;
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'content_variant_type' => CategoryPageContentVariantType::TYPE,
        ]);
    }
}
