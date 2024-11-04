<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGValueType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\CategorySortOrderGridType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The form type to create new Category instances.
 */
class CategoryType extends AbstractType
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.catalog.category.titles.label',
                    'required' => true,
                    'value_class' => CategoryTitle::class,
                    'entry_options' => ['constraints' => [
                        new NotBlank(['message' => 'oro.catalog.category.title.blank'])]
                    ],
                ]
            )
            ->add(
                'shortDescriptions',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.catalog.category.short_descriptions.label',
                    'required' => false,
                    'value_class' => CategoryShortDescription::class,
                    'field' => 'text',
                    'entry_type' => OroRichTextType::class,
                    'entry_options' => [
                        'wysiwyg_options' => [
                            'autoRender' => false,
                            'elementpath' => true,
                            'resize' => true,
                            'height' => 200,
                        ],
                    ],
                    'use_tabs' => true,
                ]
            )
            ->add(
                'longDescriptions',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.catalog.category.long_descriptions.label',
                    'required' => false,
                    'value_class' => CategoryLongDescription::class,
                    'field' => ['wysiwyg', 'wysiwyg_style', 'wysiwyg_properties'],
                    'entry_type' => WYSIWYGValueType::class,
                    'entry_options' => [
                        'entity_class' => CategoryLongDescription::class,
                        'error_mapping' => ['wysiwygStyle' => 'wysiwyg_style'],
                    ],
                    'use_tabs' => true,
                ]
            )
            ->add(
                'appendProducts',
                EntityIdentifierType::class,
                [
                    'class' => Product::class,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeProducts',
                EntityIdentifierType::class,
                [
                    'class' => Product::class,
                    'required' => false,
                    'mapped' => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'sortOrder',
                CategorySortOrderGridType::class,
                [
                    'required' => false,
                    'mapped' => false
                ]
            )
            ->add(
                'smallImage',
                ImageType::class,
                [
                    'label' => 'oro.catalog.category.small_image.label',
                    'required' => false
                ]
            )
            ->add(
                'largeImage',
                ImageType::class,
                [
                    'label' => 'oro.catalog.category.large_image.label',
                    'required' => false
                ]
            )
            ->add(
                'defaultProductOptions',
                CategoryDefaultProductOptionsType::class,
                [
                    'required' => false
                ]
            )
            ->add(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::class,
                [
                    'label' => 'oro.catalog.category.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'titles',
                    'allow_slashes' => true,
                ]
            )
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $category = $event->getData();
                if ($category instanceof Category && $category->getId()) {
                    $url = $this->urlGenerator->generate(
                        'oro_catalog_category_get_changed_slugs',
                        ['id' => $category->getId()]
                    );
                    $event->getForm()->add(
                        'slugPrototypesWithRedirect',
                        LocalizedSlugWithRedirectType::class,
                        [
                            'label' => 'oro.catalog.category.slug_prototypes.label',
                            'required' => false,
                            'source_field' => 'names',
                            'get_changed_slugs_url' => $url,
                            'allow_slashes' => true,
                        ]
                    );
                }
            });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('data_class', Category::class)
            ->setDefault('csrf_token_id', 'category');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_catalog_category';
    }
}
