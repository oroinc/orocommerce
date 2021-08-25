<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryDefaultProductOptionsType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use Oro\Bundle\CatalogBundle\Visibility\CategoryDefaultProductUnitOptionsVisibilityInterface;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGValueType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const DATA_CLASS = 'Oro\Bundle\CatalogBundle\Entity\Category';
    const PRODUCT_CLASS = 'Oro\Bundle\ProductBundle\Entity\Product';

    /**
     * @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlGenerator;

    /**
     * @var CategoryType
     */
    protected $type;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->type = new CategoryType($this->urlGenerator);
        $this->type->setDataClass(self::DATA_CLASS);
        $this->type->setProductClass(self::PRODUCT_CLASS);
        parent::setUp();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildForm()
    {
        /** @var FormBuilder|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
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
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'shortDescriptions',
                LocalizedFallbackValueCollectionType::class,
                array_merge(
                    $this->getOroRichTextTypeConfiguration('oro.catalog.category.short_descriptions.label'),
                    ['value_class' => CategoryShortDescription::class,]
                )
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(2))
            ->method('add')
            ->with(
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
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'appendProducts',
                EntityIdentifierType::class,
                ['class' => self::PRODUCT_CLASS, 'required' => false, 'mapped' => false, 'multiple' => true]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(4))
            ->method('add')
            ->with(
                'removeProducts',
                EntityIdentifierType::class,
                ['class' => self::PRODUCT_CLASS, 'required' => false, 'mapped' => false, 'multiple' => true]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(5))
            ->method('add')
            ->with(
                'smallImage',
                ImageType::class,
                ['label' => 'oro.catalog.category.small_image.label', 'required' => false]
            )->will($this->returnSelf());

        $builder->expects($this->at(6))
            ->method('add')
            ->with(
                'largeImage',
                ImageType::class,
                ['label' => 'oro.catalog.category.large_image.label', 'required' => false]
            )->will($this->returnSelf());

        $builder->expects($this->at(7))
            ->method('add')
            ->with(
                'defaultProductOptions',
                CategoryDefaultProductOptionsType::class,
                ['required' => false]
            )->will($this->returnSelf());

        $builder->expects($this->at(8))
            ->method('add')
            ->with(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::class,
                [
                    'label' => 'oro.catalog.category.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'titles',
                    'allow_slashes' => true,
                ]
            )->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => self::DATA_CLASS,
                    'csrf_token_id' => 'category',
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testGenerateChangedSlugsUrlOnPresetData()
    {
        $generatedUrl = '/some/url';
        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('oro_catalog_category_get_changed_slugs', ['id' => 1])
            ->willReturn($generatedUrl);

        /** @var Category $existingData */
        $existingData = $this->getEntity(CategoryStub::class, [
            'id' => 1,
            'slugPrototypes' => new ArrayCollection([$this->getEntity(LocalizedFallbackValue::class)])
        ]);

        /** @var Form $form */
        $form = $this->factory->create(CategoryType::class, $existingData);

        $formView = $form->createView();

        $this->assertArrayHasKey('slugPrototypesWithRedirect', $formView->children);
        $this->assertEquals(
            $generatedUrl,
            $formView->children['slugPrototypesWithRedirect']
                ->vars['confirm_slug_change_component_options']['changedSlugsUrl']
        );
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var ConfirmSlugChangeFormHelper|\PHPUnit\Framework\MockObject\MockObject $confirmHelper */
        $confirmHelper = $this->getMockBuilder(ConfirmSlugChangeFormHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CategoryDefaultProductUnitOptionsVisibilityInterface $visibilityProvider */
        $visibilityProvider = $this->createMock(CategoryDefaultProductUnitOptionsVisibilityInterface::class);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    ImageType::class => new ImageTypeStub(),
                    EntityIdentifierType::class => new EntityType([
                        1 => $this->getEntity(Category::class, ['id' => 1])
                    ]),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    CategoryDefaultProductOptionsType::class => new CategoryDefaultProductOptionsType(),
                    LocalizedSlugType::class => new LocalizedSlugTypeStub(),
                    LocalizedSlugWithRedirectType::class => new LocalizedSlugWithRedirectType($confirmHelper),
                    CategoryUnitPrecisionType::class => new CategoryUnitPrecisionType($visibilityProvider)
                ],
                []
            ),
        ];
    }

    /**
     * @param string $label
     * @return array
     */
    protected function getOroRichTextTypeConfiguration($label)
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
