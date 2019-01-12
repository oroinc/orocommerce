<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FrontendBundle\Form\Type\PageTemplateType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
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
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as StubEntityIdentifierType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EnumSelectType as EnumSelectTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const DATA_CLASS = \Oro\Bundle\ProductBundle\Entity\Product::class;

    /**
     * @var ProductType
     */
    protected $type;

    /**
     * @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $urlGenerator;

    /**
     * @var ChainDefaultProductUnitProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $defaultProductUnitProvider;

    /**
     * @var array
     */
    protected $submitCustomFields = [
        'size' => [
            'priority' => 0,
            'is_selected' => true,
        ],
        'color' => [
            'priority' => 1,
            'is_selected' => true,
        ],
    ];

    /**
     * @var AttributeFamily
     */
    protected $attributeFamily;

    /**
     * @var array
     */
    protected $images = [];

    /**
     * @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productUnitLabelFormatter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->defaultProductUnitProvider = $this
            ->getMockBuilder(ChainDefaultProductUnitProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultProductUnitProvider
            ->expects($this->any())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue($this->getDefaultProductUnitPrecision()));

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->type = new ProductType($this->defaultProductUnitProvider, $this->urlGenerator);
        $this->type->setDataClass(self::DATA_CLASS);

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
    protected function tearDown()
    {
        unset($this->type, $this->roundingService);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        $productPrimaryUnitPrecision = new ProductPrimaryUnitPrecisionType();
        $productPrimaryUnitPrecision->setDataClass(ProductUnitPrecision::class);

        $productUnitPrecision = new ProductUnitPrecisionType();
        $productUnitPrecision->setDataClass(ProductUnitPrecision::class);

        $stubEnumSelectType = new EnumSelectTypeStub([
            new StubEnumValue(Product::INVENTORY_STATUS_IN_STOCK, 'In Stock')
        ]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider $configProvider */
        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject|Translator $translator */
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject|ImageTypeProvider $imageTypeProvider*/
        $imageTypeProvider = $this->getMockBuilder(ImageTypeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $imageTypeProvider->expects($this->any())
            ->method('getImageTypes')
            ->willReturn([]);
        /** @var \PHPUnit\Framework\MockObject\MockObject|VariantFieldProvider $variantFieldProvider */
        $variantFieldProvider = $this->getMockBuilder(VariantFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $variantFields = [new VariantField('size', 'Size'), new VariantField('color', 'Color')];
        $variantFieldProvider->expects($this->any())
            ->method('getVariantFields')
            ->willReturn($variantFields);
        /** @var \PHPUnit\Framework\MockObject\MockObject|EntityFallbackResolver $entityFallbackResolver */
        $entityFallbackResolver = $this->getMockBuilder(EntityFallbackResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityFallbackResolver->expects($this->any())
            ->method('getFallbackConfig')
            ->willReturn([]);
        /** @var \PHPUnit\Framework\MockObject\MockObject|PageTemplatesManager $pageTemplatesManager */
        $pageTemplatesManager = $this->getMockBuilder(PageTemplatesManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pageTemplatesManager->expects($this->any())
            ->method('getRoutePageTemplates')
            ->willReturn([]);

        /** @var ConfirmSlugChangeFormHelper $confirmSlugChangeFormHelper */
        $confirmSlugChangeFormHelper = $this->getMockBuilder(ConfirmSlugChangeFormHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityType = new EntityTypeStub(
            [
                'each' => (new ProductUnit())->setCode('each'),
                'item' => (new ProductUnit())->setCode('item'),
                'kg' => (new ProductUnit())->setCode('kg')
            ]
        );

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    EnumSelectType::class => $stubEnumSelectType,
                    ImageType::class => new ImageTypeStub(),
                    ProductPrimaryUnitPrecisionType::class => $productPrimaryUnitPrecision,
                    ProductUnitPrecisionType::class => $productUnitPrecision,
                    ProductUnitSelectType::class => new ProductUnitSelectType($this->productUnitLabelFormatter),
                    EntityType::class => $entityType,
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    ProductCustomVariantFieldsCollectionType::class => new ProductCustomVariantFieldsCollectionType(
                        $variantFieldProvider
                    ),
                    EntityIdentifierType::class => new StubEntityIdentifierType([]),
                    ProductStatusType::class => new ProductStatusType(new ProductStatusProvider()),
                    ProductImageCollectionType::class => new ProductImageCollectionType($imageTypeProvider),
                    LocalizedSlugType::class => new LocalizedSlugTypeStub(),
                    EntityFieldFallbackValueType::class => new EntityFieldFallbackValueType($entityFallbackResolver),
                    PageTemplateType::class => new PageTemplateType($pageTemplatesManager),
                    LocalizedSlugWithRedirectType::class
                    => new LocalizedSlugWithRedirectType($confirmSlugChangeFormHelper),
                    BrandSelectType::class => new BrandSelectTypeStub(),
                ],
                [
                    FormType::class => [
                        new TooltipFormExtension($configProvider, $translator),
                        new IntegerExtension()
                    ]
                ]
            )
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param Product $defaultData
     * @param array $submittedData
     * @param Product $expectedData
     */
    public function testSubmit(Product $defaultData, $submittedData, Product $expectedData)
    {
        $form = $this->factory->create(ProductType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var Product $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function submitProvider()
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
                        ['string' => 'first name'],
                        ['string' => 'second name'],
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
                'defaultData'   => $this->createDefaultProductEntity(false),
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
                'expectedData'  => $this->createExpectedProductEntity(false, false)
            ],
            'simple product with images' => [
                'defaultData'   => $this->createDefaultProductEntity(false),
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
                'expectedData'  => $this->createExpectedProductEntity(false, false)
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

    /**
     * @param bool|false $withProductUnitPrecision
     * @param bool|false $withNamesAndDescriptions
     * @param bool|true $hasVariants
     * @param bool|true hasImages
     * @return Product
     */
    protected function createExpectedProductEntity(
        $withProductUnitPrecision = false,
        $withNamesAndDescriptions = false,
        $hasVariants = false,
        $hasImages = false
    ) {
        $expectedProduct = new Product();

        $expectedProduct
            ->setType(Product::TYPE_SIMPLE)
            ->setInventoryStatus(new StubEnumValue('in_stock', 'In Stock'));

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
                ->addName($this->createLocalizedValue('first name'))
                ->addName($this->createLocalizedValue('second name'))
                ->addDescription($this->createLocalizedValue(null, 'first description'))
                ->addDescription($this->createLocalizedValue(null, 'second description'))
                ->addShortDescription($this->createLocalizedValue(null, 'first short description'))
                ->addShortDescription($this->createLocalizedValue(null, 'second short description'));
        }

        if ($hasImages) {
            foreach ($this->images as $image) {
                $expectedProduct->addImage($image);
            }
        }

        $expectedProduct->setPageTemplate(new EntityFieldFallbackValue());
        $expectedProduct->setAttributeFamily($this->getAttributeFamily());

        $expectedProduct->setInventoryStatus(new StubEnumValue(Product::INVENTORY_STATUS_IN_STOCK, 'In Stock'));

        return $expectedProduct->setSku('test sku');
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ProductType::class, $this->createDefaultProductEntity());

        $this->assertTrue($form->has('sku'));
        $this->assertTrue($form->has('primaryUnitPrecision'));
        $this->assertTrue($form->has('additionalUnitPrecisions'));
    }

    /**
     * @param string|null $string
     * @param string|null $text
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($string = null, $text = null)
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string)
            ->setText($text);

        return $value;
    }


    /**
     * @return AttributeFamily
     */
    private function getAttributeFamily()
    {
        if (!$this->attributeFamily) {
            $this->attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => 777]);
        }

        return $this->attributeFamily;
    }
    /**
     * @param bool|true $hasVariants
     * @return Product
     */
    protected function createDefaultProductEntity($hasVariants = false)
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

    /**
     * @return ProductUnitPrecision
     */
    protected function getDefaultProductUnitPrecision()
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
        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('oro_product_get_changed_slugs', ['id' => 1])
            ->willReturn($generatedUrl);

        /** @var Product $existingData */
        $existingData = $this->getEntity(Product::class, [
            'id' => 1,
            'slugPrototypes' => new ArrayCollection([$this->getEntity(LocalizedFallbackValue::class)]),
            'directlyPrimaryUnitPrecision' => $this->getEntity(ProductUnitPrecision::class)
        ]);

        $existingData->setAttributeFamily($this->getAttributeFamily());

        /** @var Form $form */
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
