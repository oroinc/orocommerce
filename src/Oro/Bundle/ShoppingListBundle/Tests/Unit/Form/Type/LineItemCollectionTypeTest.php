<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Form\Type\LineItemType;
use Oro\Bundle\ShoppingListBundle\Form\Type\LineItemCollectionType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Symfony\Component\Form\PreloadedExtension;

class LineItemCollectionTypeTest extends AbstractFormIntegrationTestCase
{
    use QuantityTypeTrait;

    /**
     * @var LineItemCollectionType
     */
    private $lineItemCollectionType;

    /**
     * @var array
     */
    private $units = [
        'item',
        'kg'
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->lineItemCollectionType = new LineItemCollectionType();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submitted
     * @param array|LineItem[] $expected
     */
    public function testSubmit(array $submitted, array $expected = null)
    {
        $form = $this->factory->create($this->lineItemCollectionType);
        $form->submit($submitted);

        $this->assertEmpty($form->getErrors(true)->count());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $expectedProduct = $this->getProductEntityWithPrecision(1, 'item', 3);
        $expectedResult = [
            'lineItems' =>
                [
                    [
                        'product' => $expectedProduct,
                        'unit' => $expectedProduct->getUnitPrecision('item')->getUnit(),
                        'quantity' => null,
                        'notes' => null,
                    ],
                    [
                        'product' => $expectedProduct,
                        'unit' => $expectedProduct->getUnitPrecision('item')->getUnit(),
                        'quantity' => null,
                        'notes' => null,
                    ]
                ]
        ];

        return [
            'test' => [
                'submitted' => [
                    LineItemCollectionType::LINE_ITEMS_FIELD_NAME => [
                        [
                            'product' => 1,
                            'quantity' => 2,
                            'unit' => 'item'
                        ],
                        [
                            'product' => 1,
                            'quantity' => 7,
                            'unit' => 'item'
                        ],
                    ]
                ],
                'expected' => $expectedResult
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $productSelectType = new ProductSelectTypeStub();
        $entityType = new EntityType([
            1 => $this->getProductEntityWithPrecision(1, 'item', 3),
            2 => $this->getProductEntityWithPrecision(1, 'item', 3)
        ]);
        $productUnitSelection = new ProductUnitSelectionTypeStub($this->prepareProductUnitSelectionChoices());

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME => new CollectionType(),
                    LineItemCollectionType::NAME => new LineItemCollectionType(),
                    LineItemType::NAME => new LineItemType(),
                    $productSelectType->getName()  => $productSelectType,
                    $entityType->getName() => $entityType,
                    ProductUnitSelectionType::NAME => $productUnitSelection,
                    QuantityTypeTrait::$name       => $this->getQuantityType(),
                ],
                []
            ),
        ];
    }

    public function testGetName()
    {
        $this->assertSame(LineItemCollectionType::NAME, $this->lineItemCollectionType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertSame(LineItemCollectionType::NAME, $this->lineItemCollectionType->getBlockPrefix());
    }

    /**
     * @return array
     */
    private function prepareProductUnitSelectionChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }
}
