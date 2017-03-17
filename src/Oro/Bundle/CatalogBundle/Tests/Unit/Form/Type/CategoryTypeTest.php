<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use Oro\Bundle\CatalogBundle\Visibility\CategoryDefaultProductUnitOptionsVisibilityInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as StubEntityIdentifierType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryDefaultProductOptionsType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;

class CategoryTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const DATA_CLASS = 'Oro\Bundle\CatalogBundle\Entity\Category';
    const PRODUCT_CLASS = 'Oro\Bundle\ProductBundle\Entity\Product';

    /**
     * @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlGenerator;

    /**
     * @var CategoryType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->type = new CategoryType($this->urlGenerator);
        $this->type->setDataClass(self::DATA_CLASS);
        $this->type->setProductClass(self::PRODUCT_CLASS);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildForm()
    {
        /** @var FormBuilder|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'parentCategory',
                EntityIdentifierType::NAME,
                ['class' => self::DATA_CLASS, 'multiple' => false]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.catalog.category.titles.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'shortDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                $this->getOroRichTextTypeConfiguration('oro.catalog.category.short_descriptions.label')
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'longDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                $this->getOroRichTextTypeConfiguration('oro.catalog.category.long_descriptions.label')
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(4))
            ->method('add')
            ->with(
                'appendProducts',
                EntityIdentifierType::NAME,
                ['class' => self::PRODUCT_CLASS, 'required' => false, 'mapped' => false, 'multiple' => true]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(5))
            ->method('add')
            ->with(
                'removeProducts',
                EntityIdentifierType::NAME,
                ['class' => self::PRODUCT_CLASS, 'required' => false, 'mapped' => false, 'multiple' => true]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(6))
            ->method('add')
            ->with(
                'smallImage',
                'oro_image',
                ['label' => 'oro.catalog.category.small_image.label', 'required' => false]
            )->will($this->returnSelf());

        $builder->expects($this->at(7))
            ->method('add')
            ->with(
                'largeImage',
                'oro_image',
                ['label' => 'oro.catalog.category.large_image.label', 'required' => false]
            )->will($this->returnSelf());

        $builder->expects($this->at(8))
            ->method('add')
            ->with(
                'defaultProductOptions',
                CategoryDefaultProductOptionsType::NAME,
                ['required' => false]
            )->will($this->returnSelf());

        $builder->expects($this->at(9))
            ->method('add')
            ->with(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::NAME,
                [
                    'label' => 'oro.catalog.category.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'titles'
                ]
            )->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => self::DATA_CLASS,
                    'intention' => 'category',
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryType::NAME, $this->type->getName());
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
        $form = $this->factory->create($this->type, $existingData);

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
        /** @var ConfirmSlugChangeFormHelper|\PHPUnit_Framework_MockObject_MockObject $confirmHelper */
        $confirmHelper = $this->getMockBuilder(ConfirmSlugChangeFormHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CategoryDefaultProductUnitOptionsVisibilityInterface $visibilityProvider */
        $visibilityProvider = $this->createMock(CategoryDefaultProductUnitOptionsVisibilityInterface::class);

        return [
            new PreloadedExtension(
                [
                    ImageType::NAME => new ImageTypeStub(),
                    EntityIdentifierType::NAME => new StubEntityIdentifierType([
                        1 => $this->getEntity(Category::class, ['id' => 1])
                    ]),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    CategoryDefaultProductOptionsType::NAME => new CategoryDefaultProductOptionsType(),
                    LocalizedSlugType::NAME => new LocalizedSlugTypeStub(),
                    LocalizedSlugWithRedirectType::NAME => new LocalizedSlugWithRedirectType($confirmHelper),
                    CategoryUnitPrecisionType::NAME => new CategoryUnitPrecisionType($visibilityProvider)
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
            'type' => OroRichTextType::NAME,
            'options' => [
                'wysiwyg_options' => [
                    'statusbar' => true,
                    'resize' => true,
                    'width' => 500,
                    'height' => 200,
                ],
            ]
        ];
    }
}
