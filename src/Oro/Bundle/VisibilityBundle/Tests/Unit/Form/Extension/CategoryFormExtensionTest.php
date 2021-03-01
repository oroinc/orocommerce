<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryDefaultProductOptionsType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\CatalogBundle\Visibility\CategoryDefaultProductUnitOptionsVisibilityInterface;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub\ImageTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub\OroRichTextTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\DataChangesetTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Bundle\VisibilityBundle\Form\Extension\CategoryFormExtension;
use Oro\Bundle\VisibilityBundle\Form\Type\EntityVisibilityType;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityChoicesProvider;
use Oro\Bundle\VisibilityBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validation;

class CategoryFormExtensionTest extends FormIntegrationTestCase
{
    /** @var CategoryFormExtension|\PHPUnit\Framework\MockObject\MockObject */
    protected $categoryFormExtension;

    protected function setUp(): void
    {
        $this->categoryFormExtension = new CategoryFormExtension();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $registry = $this->createMock(ManagerRegistry::class);

        $postSetDataListener = $this->createMock(VisibilityPostSetDataListener::class);

        $visibilityChoicesProvider = $this->createMock(VisibilityChoicesProvider::class);
        $visibilityChoicesProvider->expects($this->any())
            ->method('getFormattedChoices')
            ->willReturn([]);

        $defaultProductOptionsVisibility = $this->createMock(
            CategoryDefaultProductUnitOptionsVisibilityInterface::class
        );

        $defaultProductOptions = new CategoryDefaultProductOptionsType();
        $defaultProductOptions->setDataClass(CategoryDefaultProductOptions::class);

        $categoryUnitPrecision = new CategoryUnitPrecisionType($defaultProductOptionsVisibility);
        $categoryUnitPrecision->setDataClass(CategoryUnitPrecision::class);

        $confirmSlugChangeFormHelper = $this->createMock(ConfirmSlugChangeFormHelper::class);
        $router = $this->createMock(RouterInterface::class);
        $purifierScopeProvider = $this->createMock(HTMLPurifierScopeProvider::class);
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $digitalAssetTwigTagsConverter = $this->createMock(DigitalAssetTwigTagsConverter::class);
        $digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToUrls')
            ->willReturnArgument(0);
        $digitalAssetTwigTagsConverter->expects(self::any())
            ->method('convertToTwigTags')
            ->willReturnArgument(0);

        return [
            new PreloadedExtension(
                [
                    EntityVisibilityType::class => new EntityVisibilityType(
                        $postSetDataListener,
                        $visibilityChoicesProvider
                    ),
                    CategoryType::class => new CategoryType($router),
                    CategoryDefaultProductOptionsType::class => $defaultProductOptions,
                    CategoryUnitPrecisionType::class => $categoryUnitPrecision,
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub(
                        [
                            'item' => (new ProductUnit())->setCode('item'),
                            'kg' => (new ProductUnit())->setCode('kg')
                        ]
                    ),
                    EntityIdentifierType::class => new EntityType([]),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionType($registry),
                    LocalizedPropertyType::class => new LocalizedPropertyType(),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    DataChangesetType::class => new DataChangesetTypeStub(),
                    EntityChangesetType::class => new EntityChangesetTypeStub(),
                    OroRichTextType::class => new OroRichTextTypeStub(),
                    ImageType::class => new ImageTypeStub(),
                    LocalizedSlugType::class => new LocalizedSlugTypeStub(),
                    LocalizedSlugWithRedirectType::class
                        => new LocalizedSlugWithRedirectType($confirmSlugChangeFormHelper),
                    WYSIWYGType::class => new WYSIWYGType(
                        $htmlTagProvider,
                        $purifierScopeProvider,
                        $digitalAssetTwigTagsConverter
                    )
                ],
                [
                    CategoryType::class => [$this->categoryFormExtension],
                    FormType::class => [new IntegerExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(
            CategoryType::class,
            new CategoryStub(),
            ['data_class' => Category::class]
        );
        $this->assertTrue($form->has('visibility'));
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([CategoryType::class], CategoryFormExtension::getExtendedTypes());
    }
}
