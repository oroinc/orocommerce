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
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validation;

class CategoryFormExtensionTest extends FormIntegrationTestCase
{
    use WysiwygAwareTestTrait;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $visibilityChoicesProvider = $this->createMock(VisibilityChoicesProvider::class);
        $visibilityChoicesProvider->expects(self::any())
            ->method('getFormattedChoices')
            ->willReturn([]);

        $defaultProductOptions = new CategoryDefaultProductOptionsType();
        $defaultProductOptions->setDataClass(CategoryDefaultProductOptions::class);

        $categoryUnitPrecision = new CategoryUnitPrecisionType(
            $this->createMock(CategoryDefaultProductUnitOptionsVisibilityInterface::class)
        );
        $categoryUnitPrecision->setDataClass(CategoryUnitPrecision::class);

        return [
            new PreloadedExtension(
                [
                    new EntityVisibilityType(
                        $this->createMock(VisibilityPostSetDataListener::class),
                        $visibilityChoicesProvider
                    ),
                    new CategoryType($this->createMock(RouterInterface::class)),
                    $defaultProductOptions,
                    CategorySortOrderGridType::class => new CategorySortOrderGridTypeStub(),
                    $categoryUnitPrecision,
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub([
                        'item' => (new ProductUnit())->setCode('item'),
                        'kg' => (new ProductUnit())->setCode('kg')
                    ]),
                    EntityIdentifierType::class => new EntityTypeStub(),
                    new LocalizedFallbackValueCollectionType($this->createMock(ManagerRegistry::class)),
                    new LocalizedPropertyType(),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    DataChangesetType::class => new DataChangesetTypeStub(),
                    EntityChangesetType::class => new EntityChangesetTypeStub(),
                    OroRichTextType::class => new OroRichTextTypeStub(),
                    ImageType::class => new ImageTypeStub(),
                    LocalizedSlugType::class => new LocalizedSlugTypeStub(),
                    new LocalizedSlugWithRedirectType($this->createMock(ConfirmSlugChangeFormHelper::class)),
                    $this->createWysiwygType(),
                ],
                [
                    CategoryType::class => [new CategoryFormExtension()],
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
