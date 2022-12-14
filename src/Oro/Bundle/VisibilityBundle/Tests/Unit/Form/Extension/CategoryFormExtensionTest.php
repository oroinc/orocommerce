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
use Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type\Stub\CategorySortOrderGridTypeStub;
use Oro\Bundle\CatalogBundle\Visibility\CategoryDefaultProductUnitOptionsVisibilityInterface;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\WysiwygAwareTestTrait;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub\ImageTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub\OroRichTextTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\DataChangesetTypeStub;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityChangesetTypeStub;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\CategorySortOrderGridType;
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
    use WysiwygAwareTestTrait;

    private CategoryFormExtension|\PHPUnit\Framework\MockObject\MockObject $categoryFormExtension;

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
        $visibilityChoicesProvider->expects(self::any())
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

        return [
            new PreloadedExtension(
                [
                    EntityVisibilityType::class => new EntityVisibilityType(
                        $postSetDataListener,
                        $visibilityChoicesProvider
                    ),
                    CategoryType::class => new CategoryType($router),
                    CategoryDefaultProductOptionsType::class => $defaultProductOptions,
                    CategorySortOrderGridType::class => new CategorySortOrderGridTypeStub(),
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
                    WYSIWYGType::class => $this->createWysiwygType(),
                ],
                [
                    CategoryType::class => [$this->categoryFormExtension],
                    FormType::class => [new IntegerExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(
            CategoryType::class,
            new CategoryStub(),
            ['data_class' => Category::class]
        );
        self::assertTrue($form->has('visibility'));
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([CategoryType::class], CategoryFormExtension::getExtendedTypes());
    }
}
