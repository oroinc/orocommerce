<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType as OroCollectionType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FrontendBundle\Form\Type\PageTemplateType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductCustomVariantFieldsCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductPrimaryUnitPrecisionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductImageCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductImageType;
use Oro\Bundle\ProductBundle\Form\Type\ProductStatusType;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductVariantFieldType;
use Oro\Bundle\ProductBundle\Form\Type\ProductVariantLinksType;
use Oro\Bundle\ProductBundle\Provider\ChainDefaultProductUnitProvider;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\ProductStatusProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\EnumSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ImageTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductCustomVariantFieldsCollectionTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as StubEntityIdentifierType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\ProductBundle\Entity\Product';

    /**
     * @var ProductType
     */
    protected $type;

    /**
     * @var RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    /** @var  ChainDefaultProductUnitProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $defaultProductUnitProvider;

    /**
     * @var array
     */
    protected $exampleCustomFields = [
        'size' => [
            'name' => 'size',
            'type' => 'boolean',
            'label' => 'Size',
            'is_serialized' => false,
        ],
        'color' => [
            'name' => 'color',
            'type' => 'enum',
            'label' => 'Color',
            'is_serialized' => false,
        ],
    ];

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
     * @var string
     */
    private $productClass = 'stdClass';

    /**
     * @var array
     */
    protected $images = [];

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->defaultProductUnitProvider = $this
            ->getMockBuilder(ChainDefaultProductUnitProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultProductUnitProvider
            ->expects($this->any())
            ->method('getDefaultProductUnitPrecision')
            ->will($this->returnValue($this->getDefaultProductUnitPrecision()));

        $this->type = new ProductType($this->defaultProductUnitProvider, $this->roundingService);
        $this->type->setDataClass(self::DATA_CLASS);

        $image1 = new StubProductImage();
        $image1->setImage(new File());

        $image2 = new StubProductImage();
        $image2->setImage(new File());

        $this->images = [$image1, $image2];

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

        $stubEnumSelectType = new EnumSelectTypeStub();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider $configProvider */
        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|Translator $translator */
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|ImageTypeProvider $imageTypeProvider*/
        $imageTypeProvider = $this->getMockBuilder(ImageTypeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $imageTypeProvider->expects($this->any())
            ->method('getImageTypes')
            ->willReturn([]);
        /** @var \PHPUnit_Framework_MockObject_MockObject|CustomFieldProvider $customFieldProvider */
        $customFieldProvider = $this->getMockBuilder(CustomFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customFieldProvider->expects($this->any())
            ->method('getEntityCustomFields')
            ->willReturn($this->exampleCustomFields);
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityFallbackResolver $entityFallbackResolver */
        $entityFallbackResolver = $this->getMockBuilder(EntityFallbackResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityFallbackResolver->expects($this->any())
            ->method('getFallbackConfig')
            ->willReturn([]);
        /** @var \PHPUnit_Framework_MockObject_MockObject|PageTemplatesManager $pageTemplatesManager */
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

        return [
            new PreloadedExtension(
                [
                    $stubEnumSelectType->getName() => $stubEnumSelectType,
                    ImageType::NAME => new ImageTypeStub(),
                    OroCollectionType::NAME => new OroCollectionType(),
                    ProductPrimaryUnitPrecisionType::NAME => $productPrimaryUnitPrecision,
                    ProductUnitPrecisionType::NAME => $productUnitPrecision,
                    ProductUnitPrecisionCollectionType::NAME => new ProductUnitPrecisionCollectionType(),
                    ProductUnitSelectionType::NAME => new ProductUnitSelectionTypeStub(
                        [
                            'item' => (new ProductUnit())->setCode('item'),
                            'kg' => (new ProductUnit())->setCode('kg')
                        ],
                        ProductUnitSelectionType::NAME
                    ),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    ProductCustomVariantFieldsCollectionType::NAME => new ProductCustomVariantFieldsCollectionTypeStub(
                        $customFieldProvider,
                        $this->productClass,
                        $this->exampleCustomFields
                    ),
                    EntityIdentifierType::NAME => new StubEntityIdentifierType([]),
                    ProductVariantLinksType::NAME => new ProductVariantLinksType(),
                    ProductStatusType::NAME => new ProductStatusType(new ProductStatusProvider()),
                    ProductImageCollectionType::NAME => new ProductImageCollectionType($imageTypeProvider),
                    ProductImageType::NAME => new ProductImageType(),
                    LocalizedSlugType::NAME => new LocalizedSlugTypeStub(),
                    ProductVariantFieldType::NAME => new ProductVariantFieldType(),
                    EntityFieldFallbackValueType::NAME => new EntityFieldFallbackValueType($entityFallbackResolver),
                    PageTemplateType::class => new PageTemplateType($pageTemplatesManager),
                    LocalizedSlugWithRedirectType::NAME
                    => new LocalizedSlugWithRedirectType($confirmSlugChangeFormHelper),
                ],
                [
                    'form' => [
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
     * @param boolean $rounding
     */
    public function testSubmit(Product $defaultData, $submittedData, Product $expectedData, $rounding = false)
    {
        if ($rounding) {
            $this->roundingService->expects($this->once())
                ->method('round')
                ->willReturnCallback(
                    function ($value, $precision) {
                        return round($value, $precision);
                    }
                );
        }

        $form = $this->factory->create($this->type, $defaultData);

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
                ],
                'expectedData'  => $this->createExpectedProductEntity()
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('slug')),
                'rounding' => false
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
                ],
                'expectedData'  => $this->createExpectedProductEntity(true),
                'rounding' => false
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
                ],
                'expectedData'  => $this->createExpectedProductEntity(false, true),
                'rounding' => false
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
                ],
                'expectedData'  => $this->createExpectedProductEntity(false, false),
                'rounding' => false
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
                ],
                'expectedData'  => $this->createExpectedProductEntity(false, false),
                'rounding' => false
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
                ],
                'expectedData' => $this->createExpectedProductEntity(false, false, true)
                    ->setType(Product::TYPE_CONFIGURABLE),
                'rounding' => false
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

        $expectedProduct->setType(Product::TYPE_SIMPLE);

        if ($hasVariants) {
            $expectedProduct->setType(Product::TYPE_CONFIGURABLE);
            $expectedProduct->setVariantFields(array_keys($this->exampleCustomFields));
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

        $entityFieldFallbackValue = new EntityFieldFallbackValue();
        $entityFieldFallbackValue->setArrayValue([
            'oro_product_frontend_product_view' => null
        ]);
        $expectedProduct->setPageTemplate($entityFieldFallbackValue);

        return $expectedProduct->setSku('test sku');
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, $this->createDefaultProductEntity());

        $this->assertTrue($form->has('sku'));
        $this->assertTrue($form->has('primaryUnitPrecision'));
        $this->assertTrue($form->has('additionalUnitPrecisions'));
    }

    public function testGetName()
    {
        $this->assertEquals('oro_product', $this->type->getName());
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
     * @param bool|true $hasVariants
     * @return Product
     */
    protected function createDefaultProductEntity($hasVariants = false)
    {
        $defaultProduct = new Product();
        $defaultProduct->setType(Product::TYPE_SIMPLE);

        if ($hasVariants) {
            $defaultProduct->setType(Product::TYPE_CONFIGURABLE);
            $defaultProduct->setVariantFields(array_keys($this->exampleCustomFields));
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
}
