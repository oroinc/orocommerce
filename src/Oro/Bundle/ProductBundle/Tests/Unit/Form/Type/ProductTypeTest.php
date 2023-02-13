<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type\Stub\EnumSelectTypeStub;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\FrontendBundle\Form\Type\PageTemplateType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\BrandSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCustomVariantFieldsCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductImageCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductPrimaryUnitPrecisionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductStatusType;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Provider\ChainDefaultProductUnitProvider;
use Oro\Bundle\ProductBundle\Provider\ProductStatusProvider;
use Oro\Bundle\ProductBundle\Provider\VariantField;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\BrandSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductTypeTest extends FormIntegrationTestCase
{
    /** @var ProductType */
    private $type;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    private array $submitCustomFields = [
        'size' => [
            'priority' => 0,
            'is_selected' => true,
        ],
        'color' => [
            'priority' => 1,
            'is_selected' => true,
        ],
    ];

    /** @var AttributeFamily */
    private $attributeFamily;

    private array $images = [];

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitLabelFormatter;

    protected function setUp(): void
    {
        $defaultProductUnitProvider = $this->createMock(ChainDefaultProductUnitProvider::class);
        $defaultProductUnitProvider->expects($this->any())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn($this->getDefaultProductUnitPrecision());

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->type = new ProductType($defaultProductUnitProvider, $this->urlGenerator, new ProductImageHelper());
        $this->type->setDataClass(\Oro\Bundle\ProductBundle\Entity\Product::class);

        $image1 = new StubProductImage();
        $image1->setImage(new File());

        $image2 = new StubProductImage();
        $image2->setImage(new File());

        $this->images = [$image1, $image2];

        $this->productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $productPrimaryUnitPrecision = new ProductPrimaryUnitPrecisionType();
        $productPrimaryUnitPrecision->setDataClass(ProductUnitPrecision::class);

        $productUnitPrecision = new ProductUnitPrecisionType();
        $productUnitPrecision->setDataClass(ProductUnitPrecision::class);

        $imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $imageTypeProvider->expects($this->any())
            ->method('getImageTypes')
            ->willReturn([]);

        $variantFieldProvider = $this->createMock(VariantFieldProvider::class);
        $variantFieldProvider->expects($this->any())
            ->method('getVariantFields')
            ->willReturn([new VariantField('size', 'Size'), new VariantField('color', 'Color')]);

        $entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);
        $entityFallbackResolver->expects($this->any())
            ->method('getFallbackConfig')
            ->willReturn([]);

        $pageTemplatesManager = $this->createMock(PageTemplatesManager::class);
        $pageTemplatesManager->expects($this->any())
            ->method('getRoutePageTemplates')
            ->willReturn([]);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    EnumSelectType::class => new EnumSelectTypeStub([
                        new TestEnumValue(Product::INVENTORY_STATUS_IN_STOCK, 'In Stock')
                    ]),
                    ImageType::class => new ImageTypeStub(),
                    $productPrimaryUnitPrecision,
                    $productUnitPrecision,
                    new ProductUnitSelectType($this->productUnitLabelFormatter),
                    EntityType::class => new EntityTypeStub([
                        'each' => (new ProductUnit())->setCode('each'),
                        'item' => (new ProductUnit())->setCode('item'),
                        'kg' => (new ProductUnit())->setCode('kg')
                    ]),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    new ProductCustomVariantFieldsCollectionType($variantFieldProvider),
                    EntityIdentifierType::class => new EntityTypeStub(),
                    new ProductStatusType(new ProductStatusProvider()),
                    new ProductImageCollectionType($imageTypeProvider),
                    LocalizedSlugType::class => new LocalizedSlugTypeStub(),
                    new EntityFieldFallbackValueType($entityFallbackResolver),
                    new PageTemplateType($pageTemplatesManager),
                    new LocalizedSlugWithRedirectType($this->createMock(ConfirmSlugChangeFormHelper::class)),
                    BrandSelectType::class => new BrandSelectTypeStub(),
                    new EntityTypeStub([
                        'en' => (new Localization())->setName('en'),
                        'en_US' => (new Localization())->setName('en_US'),
                    ]),
                ],
                [
                    FormType::class => [
                        new TooltipFormExtensionStub($this),
                        new IntegerExtension()
                    ]
                ]
            )
        ];
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(Product $defaultData, array $submittedData, Product $expectedData)
    {
        $form = $this->factory->create(ProductType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var Product $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider(): array
    {
        return [
            'simple product' => [
                'defaultData'   => $this->createDefaultProductEntity(),
                'submittedData' => [
                    'sku' => 'test sku',
                    'primaryUnitPrecision' => ['unit' => 'each', 'precision' => 0],
                    'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'type' => Product::TYPE_SIMPLE,
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'slug']],
                        'createRedirect' => true,
                    ],
                    'featured' => 1,
                    'attributeFamily' => $this->getAttributeFamily()
                ],
                'expectedData'  => $this->createExpectedProductEntity()
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('slug'))
                    ->setFeatured(true)
            ],
            'product with additionalUnitPrecisions' => [
                'defaultData'   => $this->createDefaultProductEntity(),
                'submittedData' => [
                    'sku' => 'test sku',
                    'primaryUnitPrecision' => ['unit' => 'each', 'precision' => 0],
                    'additionalUnitPrecisions' => [
                        [
                            'unit' => 'kg',
                            'precision' => 3,
                            'conversionRate' => 5,
                            'sell' => true,

                        ],
                    ],
                    'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'type' => Product::TYPE_SIMPLE,
                    'slugPrototypesWithRedirect' => [
                        'createRedirect' => true,
                    ],
                    'attributeFamily' => $this->getAttributeFamily()
                ],
                'expectedData'  => $this->createExpectedProductEntity(true)
            ],
            'product with names and descriptions' => [
                'defaultData'   => $this->createDefaultProductEntity(),
                'submittedData' => [
                    'sku' => 'test sku',
                    'primaryUnitPrecision' => ['unit' => 'each', 'precision' => 0],
                    'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'type' => Product::TYPE_SIMPLE,
                    'names' => [
                        ['string' => 'first name', 'localization' => 'en'],
                        ['string' => 'second name', 'localization' => 'en_US'],
                    ],
                    'descriptions' => [
                        ['text' => 'first description'],
                        ['text' => 'second description'],
                    ],
                    'shortDescriptions' => [
                        ['text' => 'first short description'],
                        ['text' => 'second short description'],
                    ],
                    'slugPrototypesWithRedirect' => [
                        'createRedirect' => true,
                    ],
                    'attributeFamily' => $this->getAttributeFamily()
                ],
                'expectedData'  => $this->createExpectedProductEntity(false, true)
            ],
            'simple product without variants' => [
                'defaultData'   => $this->createDefaultProductEntity(),
                'submittedData' => [
                    'sku' => 'test sku',
                    'primaryUnitPrecision' => ['unit' => 'each', 'precision' => 0],
                    'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'type' => Product::TYPE_SIMPLE,
                    'slugPrototypesWithRedirect' => [
                        'createRedirect' => true,
                    ],
                    'attributeFamily' => $this->getAttributeFamily()
                ],
                'expectedData'  => $this->createExpectedProductEntity()
            ],
            'simple product with images' => [
                'defaultData'   => $this->createDefaultProductEntity(),
                'submittedData' => [
                    'sku' => 'test sku',
                    'primaryUnitPrecision' => ['unit' => 'each', 'precision' => 0],
                    'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'type' => Product::TYPE_SIMPLE,
                    'images' => $this->images,
                    'slugPrototypesWithRedirect' => [
                        'createRedirect' => true,
                    ],
                    'attributeFamily' => $this->getAttributeFamily()
                ],
                'expectedData'  => $this->createExpectedProductEntity()
            ],
            'configurable product' => [
                'defaultData'   => $this->createDefaultProductEntity(true),
                'submittedData' => [
                    'sku' => 'test sku',
                    'primaryUnitPrecision' => ['unit' => 'each', 'precision' => 0],
                    'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'type' => Product::TYPE_CONFIGURABLE,
                    'variantFields' => $this->submitCustomFields,
                    'slugPrototypesWithRedirect' => [
                        'createRedirect' => true,
                    ],
                    'attributeFamily' => $this->getAttributeFamily()
                ],
                'expectedData' => $this->createExpectedProductEntity(false, false, true)
                    ->setType(Product::TYPE_CONFIGURABLE)
            ],
        ];
    }

    private function createExpectedProductEntity(
        bool $withProductUnitPrecision = false,
        bool $withNamesAndDescriptions = false,
        bool $hasVariants = false,
        bool $hasImages = false
    ): Product {
        $expectedProduct = new Product();

        $expectedProduct
            ->setType(Product::TYPE_SIMPLE)
            ->setInventoryStatus(new TestEnumValue('in_stock', 'In Stock'));

        if ($hasVariants) {
            $expectedProduct->setType(Product::TYPE_CONFIGURABLE);
            $expectedProduct->setVariantFields(['size', 'color']);
        }

        $expectedProduct->setPrimaryUnitPrecision($this->getDefaultProductUnitPrecision());

        if ($withProductUnitPrecision) {
            $productUnit = new ProductUnit();
            $productUnit->setCode('kg');

            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision
                ->setProduct($expectedProduct)
                ->setUnit($productUnit)
                ->setPrecision(3)
                ->setConversionRate(5)
                ->setSell(true);

            $expectedProduct->addAdditionalUnitPrecision($productUnitPrecision);
        }

        if ($withNamesAndDescriptions) {
            $expectedProduct
                ->setNames(
                    [
                        (new ProductName())->setString('first name')
                            ->setLocalization((new Localization())->setName('en')),
                        (new ProductName())->setString('second name')
                            ->setLocalization((new Localization())->setName('en_US'))
                    ]
                )
                ->getNames()->map(static function (ProductName $name) {
                    $name->setProduct(null);
                });

            $expectedProduct
                ->setDescriptions(
                    [
                        (new ProductDescription())->setText('first description'),
                        (new ProductDescription())->setText('second description')
                    ]
                )
                ->getDescriptions()->map(function (ProductDescription $description) {
                    $description->setProduct(null);
                });

            $expectedProduct
                ->setShortDescriptions(
                    [
                        (new ProductShortDescription())->setText('first short description'),
                        (new ProductShortDescription())->setText('second short description')
                    ]
                )
                ->getShortDescriptions()->map(function (ProductShortDescription $shortDescription) {
                    $shortDescription->setProduct(null);
                });
        }

        if ($hasImages) {
            foreach ($this->images as $image) {
                $expectedProduct->addImage($image);
            }
        }

        $expectedProduct->setPageTemplate(new EntityFieldFallbackValue());
        $expectedProduct->setAttributeFamily($this->getAttributeFamily());

        $expectedProduct->setInventoryStatus(new TestEnumValue(Product::INVENTORY_STATUS_IN_STOCK, 'In Stock'));

        return $expectedProduct->setSku('test sku');
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ProductType::class, $this->createDefaultProductEntity());

        $this->assertTrue($form->has('sku'));
        $this->assertTrue($form->has('primaryUnitPrecision'));
        $this->assertTrue($form->has('additionalUnitPrecisions'));
    }

    private function getAttributeFamily(): AttributeFamily
    {
        if (!$this->attributeFamily) {
            $this->attributeFamily = new AttributeFamily();
            ReflectionUtil::setId($this->attributeFamily, 777);
        }

        return $this->attributeFamily;
    }

    private function createDefaultProductEntity(bool $hasVariants = false): Product
    {
        $defaultProduct = new Product();
        $defaultProduct->setType(Product::TYPE_SIMPLE);
        $defaultProduct->setAttributeFamily($this->getAttributeFamily());

        if ($hasVariants) {
            $defaultProduct->setType(Product::TYPE_CONFIGURABLE);
            $defaultProduct->setVariantFields(['size', 'color']);
        }

        return $defaultProduct;
    }

    private function getDefaultProductUnitPrecision(): ProductUnitPrecision
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode('each');

        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($productUnit)->setPrecision('0');

        return $productUnitPrecision;
    }

    public function testGenerateChangedSlugsUrlOnPresetData()
    {
        $generatedUrl = '/some/url';
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_product_get_changed_slugs', ['id' => 1])
            ->willReturn($generatedUrl);

        $existingData = new Product();
        $existingData->setId(1);
        $existingData->addSlugPrototype(new LocalizedFallbackValue());
        $existingData->setDirectlyPrimaryUnitPrecision(new ProductUnitPrecision());

        $existingData->setAttributeFamily($this->getAttributeFamily());

        $form = $this->factory->create(ProductType::class, $existingData);

        $formView = $form->createView();

        $this->assertArrayHasKey('slugPrototypesWithRedirect', $formView->children);
        $this->assertEquals(
            $generatedUrl,
            $formView->children['slugPrototypesWithRedirect']
                ->vars['confirm_slug_change_component_options']['changedSlugsUrl']
        );
    }
}
