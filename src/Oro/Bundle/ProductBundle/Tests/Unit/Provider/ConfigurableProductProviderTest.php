<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\ConfigurableProductProvider;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\Testing\Unit\EntityTrait;

class ConfigurableProductProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CustomFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customFieldProvider;

    /**
     * @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productVariantAvailabilityProvider;

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

    protected function setUp()
    {
        $this->customFieldProvider = $this->getMockBuilder(CustomFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productVariantAvailabilityProvider = $this->getMockBuilder(ProductVariantAvailabilityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurableProductProvider = new ConfigurableProductProvider(
            $this->customFieldProvider,
            $this->productVariantAvailabilityProvider,
            $this->getPropertyAccessor()
        );
    }


    public function testGetProductsWithoutLineItems()
    {
        /** @var LineItem[] $lineItems */
        $lineItems = [];
        $this->assertEquals(null, $this->configurableProductProvider->getProducts($lineItems));
    }

    public function testGetLineItemProduct()
    {
        $expectedField = [
            2 => [
                'size' => [
                    'value' => true,
                    'label' => 'Size',
                    'type' => 'boolean'
                ]
            ]
        ];
        /** @var $lineItem */
        $lineItem = $this->getEntity(LineItem::class, ['id' => 1]);
        /** @var Product $parentProduct */
        $parentProduct = $this->getEntity(Product::class, ['id' => 1]);
        $parentProduct->setVariantFields(['size']);
        $lineItem->setParentProduct($parentProduct);

        /** @var Product|\PHPUnit_Framework_MockObject_MockObject */
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
        $this->assertEquals($expectedField, $this->configurableProductProvider->getLineItemProduct($lineItem));
    }

    public function testGetProductsWithoutParentProduct()
    {
        /** @var LineItem[] $lineItems */
        $lineItems = [
            $this->getEntity(LineItem::class, ['id' => 1]),
        ];
        $this->assertEquals([], $this->configurableProductProvider->getProducts($lineItems));
    }
}
