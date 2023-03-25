<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryDefaultProductOptionsType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type\Stub\CategorySortOrderGridTypeStub;
use Oro\Bundle\CatalogBundle\Visibility\CategoryDefaultProductUnitOptionsVisibilityInterface;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGValueType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\CategorySortOrderGridType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryTypeTest extends FormIntegrationTestCase
{
    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var CategoryType */
    private $type;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->type = new CategoryType($this->urlGenerator);
        $this->type->setDataClass(Category::class);
        $this->type->setProductClass(Product::class);
        parent::setUp();
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects(self::exactly(10))
            ->method('add')
            ->withConsecutive(
                [
                    'titles',
                    LocalizedFallbackValueCollectionType::class,
                    [
                        'label' => 'oro.catalog.category.titles.label',
                        'required' => true,
                        'value_class' => CategoryTitle::class,
                        'entry_options' => ['constraints' => [
                            new NotBlank(['message' => 'oro.catalog.category.title.blank'])
                        ]],
                    ]
                ],
                [
                    'shortDescriptions',
                    LocalizedFallbackValueCollectionType::class,
                    array_merge(
                        $this->getOroRichTextTypeConfiguration('oro.catalog.category.short_descriptions.label'),
                        ['value_class' => CategoryShortDescription::class]
                    )
                ],
                [
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
                        'use_tabs' => true
                    ]
                ],
                [
                    'appendProducts',
                    EntityIdentifierType::class,
                    ['class' => Product::class, 'required' => false, 'mapped' => false, 'multiple' => true]
                ],
                [
                    'removeProducts',
                    EntityIdentifierType::class,
                    ['class' => Product::class, 'required' => false, 'mapped' => false, 'multiple' => true]
                ],
                [
                    'sortOrder',
                    CategorySortOrderGridType::class,
                    ['required' => false, 'mapped' => false]
                ],
                [
                    'smallImage',
                    ImageType::class,
                    ['label' => 'oro.catalog.category.small_image.label', 'required' => false]
                ],
                [
                    'largeImage',
                    ImageType::class,
                    ['label' => 'oro.catalog.category.large_image.label', 'required' => false]
                ],
                [
                    'defaultProductOptions',
                    CategoryDefaultProductOptionsType::class,
                    ['required' => false]
                ],
                [
                    'slugPrototypesWithRedirect',
                    LocalizedSlugWithRedirectType::class,
                    [
                        'label' => 'oro.catalog.category.slug_prototypes.label',
                        'required' => false,
                        'source_field' => 'titles',
                        'allow_slashes' => true,
                    ]
                ]
            )
            ->willReturnSelf();

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => Category::class,
                    'csrf_token_id' => 'category',
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testGenerateChangedSlugsUrlOnPresetData(): void
    {
        $generatedUrl = '/some/url';
        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_catalog_category_get_changed_slugs', ['id' => 1])
            ->willReturn($generatedUrl);

        $existingData = new CategoryStub();
        ReflectionUtil::setId($existingData, 1);
        $existingData->addSlugPrototype(new LocalizedFallbackValue());

        $form = $this->factory->create(CategoryType::class, $existingData);
        $formView = $form->createView();

        self::assertArrayHasKey('slugPrototypesWithRedirect', $formView->children);
        self::assertEquals(
            $generatedUrl,
            $formView->children['slugPrototypesWithRedirect']
                ->vars['confirm_slug_change_component_options']['changedSlugsUrl']
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    CategorySortOrderGridType::class => new CategorySortOrderGridTypeStub(),
                    ImageType::class => new ImageTypeStub(),
                    EntityIdentifierType::class => new EntityTypeStub([
                        1 => $this->getCategory(1)
                    ]),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    new CategoryDefaultProductOptionsType(),
                    LocalizedSlugType::class => new LocalizedSlugTypeStub(),
                    new LocalizedSlugWithRedirectType($this->createMock(ConfirmSlugChangeFormHelper::class)),
                    new CategoryUnitPrecisionType(
                        $this->createMock(CategoryDefaultProductUnitOptionsVisibilityInterface::class)
                    )
                ],
                []
            ),
        ];
    }

    private function getCategory(int $id): Category
    {
        $category = new Category();
        ReflectionUtil::setId($category, $id);

        return $category;
    }

    private function getOroRichTextTypeConfiguration(string $label): array
    {
        return [
            'label' => $label,
            'required' => false,
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
            'use_tabs' => true
        ];
    }
}
