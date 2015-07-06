<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType as OroCollectionType;

use OroB2B\Bundle\AttributeBundle\Form\Extension\IntegerExtension;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionCollectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubEnumSelectType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubImageType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;

class ProductTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    /**
     * @var ProductType
     */
    protected $type;

    /**
     * @var RoundingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ProductType($this->roundingService);
        $this->type->setDataClass(self::DATA_CLASS);

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
     * @return array
     */
    protected function getExtensions()
    {
        $productUnitPrecision = new ProductUnitPrecisionType();
        $productUnitPrecision->setDataClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision');

        $stubEnumSelectType = new StubEnumSelectType();

        return [
            new PreloadedExtension(
                [
                    CategoryTreeType::NAME => new StubEntityType(
                        [
                            1 => $this->createCategory('Test Category First'),
                            2 => $this->createCategory('Test Category Second'),
                            3 => $this->createCategory('Test Category Third')
                        ],
                        CategoryTreeType::NAME
                    ),
                    $stubEnumSelectType->getName() => $stubEnumSelectType,
                    ImageType::NAME => new StubImageType(),
                    OroCollectionType::NAME => new OroCollectionType(),
                    ProductUnitPrecisionType::NAME => $productUnitPrecision,
                    ProductUnitPrecisionCollectionType::NAME => new ProductUnitPrecisionCollectionType(),
                    ProductUnitSelectionType::NAME => new StubProductUnitSelectionType(
                        [
                            'item' => (new ProductUnit())->setCode('item'),
                            'kg' => (new ProductUnit())->setCode('kg')
                        ],
                        ProductUnitSelectionType::NAME
                    ),
                ],
                [
                    'form' => [
                        new TooltipFormExtension(),
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
        $expectedData
            ->getCategory()
            ->setCreatedAt($data->getCategory()->getCreatedAt())
            ->setUpdatedAt($data->getCategory()->getUpdatedAt());

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $defaultProduct = new StubProduct();

        return [
            'product without unitPrecisions' => [
                'defaultData'   => $defaultProduct,
                'submittedData' => [
                    'sku' => 'test sku',
                    'category' => 2,
                    'unitPrecisions' => [],
                    'inventoryStatus' => 'in_stock',
                    'visible' => 1,
                    'status' => 'disabled'
                ],
                'expectedData'  => $this->createExpectedProductEntity(),
                'rounding' => false
            ],
            'product with unitPrecisions' => [
                'defaultData'   => $defaultProduct,
                'submittedData' => [
                    'sku' => 'test sku',
                    'category' => 2,
                    'unitPrecisions' => [
                        [
                            'unit' => 'kg',
                            'precision' => 3
                        ]
                    ],
                    'inventoryStatus' => 'in_stock',
                    'visible' => 1,
                    'status' => 'disabled'
                ],
                'expectedData'  => $this->createExpectedProductEntity(true),
                'rounding' => false
            ],
        ];
    }

    /**
     * @param boolean $withProductUnitPrecision
     * @return Product
     */
    protected function createExpectedProductEntity($withProductUnitPrecision = false)
    {
        $expectedProduct = new StubProduct();

        $category = $this->createCategory('Test Category Second');

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        if ($withProductUnitPrecision) {
            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision
                ->setProduct($expectedProduct)
                ->setUnit($productUnit)
                ->setPrecision(3);

            $expectedProduct->addUnitPrecision($productUnitPrecision);
        }

        return $expectedProduct
            ->setSku('test sku')
            ->setCategory($category);
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('sku'));
        $this->assertTrue($form->has('category'));
        $this->assertTrue($form->has('unitPrecisions'));
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_product', $this->type->getName());
    }

    /**
     * @param string $title
     * @return Category
     */
    protected function createCategory($title)
    {
        $localizedTitle = new LocalizedFallbackValue();
        $localizedTitle->setString($title);

        $category = new Category();

        return $category->addTitle($localizedTitle);
    }
}
