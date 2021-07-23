<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigurableProductProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CustomFieldProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $customFieldProvider;

    /**
     * @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productVariantAvailabilityProvider;

    /**
     * @var ProductVariantFieldValueHandlerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productVariantFieldValueHandlerRegistry;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

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
     * @var ConfigurableProductProvider
     */
    protected $configurableProductProvider;

    protected function setUp(): void
    {
        $this->customFieldProvider = $this->getMockBuilder(CustomFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productVariantAvailabilityProvider = $this->getMockBuilder(ProductVariantAvailabilityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productVariantFieldValueHandlerRegistry =
            $this->createMock(ProductVariantFieldValueHandlerRegistry::class);

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->configurableProductProvider = new ConfigurableProductProvider(
            $this->customFieldProvider,
            $this->productVariantAvailabilityProvider,
            $this->getPropertyAccessor(),
            $this->productVariantFieldValueHandlerRegistry,
            $this->translator
        );
    }

    public function testGetProductsWithoutLineItems()
    {
        /** @var LineItem[] $lineItems */
        $lineItems = [];
        $this->assertEquals(null, $this->configurableProductProvider->getProducts($lineItems));
    }

    private function mockGetVariantFieldsValuesForLineItem(LineItem $lineItem): void
    {
        /** @var Product $parentProduct */
        $parentProduct = $this->getEntity(Product::class, ['id' => 1]);
        $parentProduct->setVariantFields(['size']);
        $lineItem->setParentProduct($parentProduct);

        /** @var Product|\PHPUnit\Framework\MockObject\MockObject */
        $simpleProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getSize'])
            ->getMock();
        $simpleProduct->expects($this->any())
            ->method('getSize')
            ->willReturn(true);
        $simpleProduct->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $lineItem->setProduct($simpleProduct);

        $this->customFieldProvider->expects($this->any())
            ->method('getEntityCustomFields')
            ->willReturn($this->exampleCustomFields);

        $boolHandler = $this->createMock(ProductVariantFieldValueHandlerInterface::class);

        $boolHandler->expects($this->atLeastOnce())
            ->method('getHumanReadableValue')
            ->willReturnCallback(function ($value) {
                return $value ? 'Yes' : 'No';
            });

        $this->productVariantFieldValueHandlerRegistry->expects($this->any())
            ->method('getVariantFieldValueHandler')
            ->with('boolean')
            ->willReturn($boolHandler);
    }

    public function testGetProductsWithoutParentProduct()
    {
        /** @var LineItem[] $lineItems */
        $lineItems = [
            $this->getEntity(LineItem::class, ['id' => 1]),
        ];
        $this->assertEquals([], $this->configurableProductProvider->getProducts($lineItems));
    }

    public function testGetVariantFieldsValuesForLineItemWhenNotTranslateLabels(): void
    {
        $this->translator
            ->expects($this->never())
            ->method($this->anything());

        $expectedField = [
            2 => [
                'size' => [
                    'value' => 'Yes',
                    'label' => 'Size',
                    'type' => 'boolean'
                ]
            ]
        ];

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntity(LineItem::class, ['id' => 1]);
        $this->mockGetVariantFieldsValuesForLineItem($lineItem);

        $this->assertEquals(
            $expectedField,
            $this->configurableProductProvider->getVariantFieldsValuesForLineItem($lineItem, false)
        );
    }

    public function testGetVariantFieldsValuesForLineItemWhenTranslateLabels(): void
    {
        $this->translator
            ->expects($this->atLeastOnce())
            ->method('trans')
            ->willReturnCallback(
                static function (string $key) {
                    return $key . 'Translated';
                }
            );

        $expectedField = [
            2 => [
                'size' => [
                    'value' => 'Yes',
                    'label' => 'SizeTranslated',
                    'type' => 'boolean'
                ]
            ]
        ];

        /** @var LineItem $lineItem */
        $lineItem = $this->getEntity(LineItem::class, ['id' => 1]);
        $this->mockGetVariantFieldsValuesForLineItem($lineItem);

        $this->assertEquals(
            $expectedField,
            $this->configurableProductProvider->getVariantFieldsValuesForLineItem($lineItem, true)
        );

        // Checks local cache.
        $this->assertEquals(
            $expectedField,
            $this->configurableProductProvider->getVariantFieldsValuesForLineItem($lineItem, true)
        );
    }
}
