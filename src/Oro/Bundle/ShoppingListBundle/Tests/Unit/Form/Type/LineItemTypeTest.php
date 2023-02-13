<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\LineItemType;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LineItemTypeTest extends AbstractFormIntegrationTestCase
{
    use QuantityTypeTrait;

    private const DATA_CLASS = LineItem::class;
    private const PRODUCT_CLASS = Product::class;

    private LineItemType $type;

    private array $units = [
        'item',
        'kg'
    ];

    protected function setUp(): void
    {
        $this->type = new LineItemType();
        $this->type->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    EntityType::class => new EntityTypeStub([
                        1 => $this->getProductEntityWithPrecision(1, 'kg', 3),
                        2 => $this->getProductEntityWithPrecision(2, 'kg', 3)
                    ]),
                    ProductSelectType::class => new ProductSelectTypeStub(),
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub(
                        $this->prepareProductUnitSelectionChoices()
                    ),
                    $this->getQuantityType(),
                ],
                []
            )
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(LineItemType::class);

        self::assertTrue($form->has('product'));
        self::assertTrue($form->has('quantity'));
        self::assertTrue($form->has('unit'));
        self::assertTrue($form->has('notes'));
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(LineItem $defaultData, array $submittedData, LineItem $expectedData): void
    {
        $form = $this->factory->create(LineItemType::class, $defaultData, []);

        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        self::assertEquals(0, $form->getErrors(true)->count());
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $shoppingList = new ShoppingList();

        $expectedProduct = $this->getProductEntityWithPrecision(1, 'kg', 3);

        $defaultLineItem = new LineItem();
        $defaultLineItem->setShoppingList($shoppingList);

        $expectedLineItem = clone $defaultLineItem;
        $expectedLineItem
            ->setProduct($expectedProduct)
            ->setQuantity('10')
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setNotes('my note');

        $existingLineItem = new LineItemStub();
        $existingLineItem
            ->setId(2)
            ->setShoppingList($shoppingList)
            ->setProduct($expectedProduct)
            ->setQuantity(5)
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setNotes('my note2');

        $expectedLineItem2 = clone $existingLineItem;
        $expectedLineItem2
            ->setQuantity(15.112)
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setNotes('note1');

        $expectedLineItem3 = clone $existingLineItem;
        $expectedLineItem3
            ->setQuantity(15.112)
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setNotes(null);

        return [
            'new line item'      => [
                'defaultData'   => $defaultLineItem,
                'submittedData' => [
                    'product'  => 1,
                    'quantity' => 10,
                    'unit'     => 'kg',
                    'notes'    => 'my note',
                ],
                'expectedData'  => $expectedLineItem,
            ],
            'existing line item' => [
                'defaultData'   => $existingLineItem,
                'submittedData' => [
                    'product'  => 2,
                    'quantity' => 15.112,
                    'unit'     => 'kg',
                    'notes'    => 'note1',
                ],
                'expectedData'  => $expectedLineItem2,
            ],
            'missing product' => [
                'defaultData'   => $existingLineItem,
                'submittedData' => [
                    'unit'     => 'kg',
                    'quantity' => 15.112,
                ],
                'expectedData'  => $expectedLineItem3,
            ],
        ];
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $lineItem = new LineItemStub();
        $lineItem2 = (new LineItemStub())->setId(1);

        self::assertEquals(self::DATA_CLASS, $resolvedOptions['data_class']);
        self::assertTrue($resolvedOptions['ownership_disabled']);
        self::assertEquals(['create'], $resolvedOptions['validation_groups']($this->getForm($lineItem)));
        self::assertEquals(['update'], $resolvedOptions['validation_groups']($this->getForm($lineItem2)));
    }

    private function getForm(LineItem $lineItem): FormInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($lineItem);

        return $form;
    }

    private function prepareProductUnitSelectionChoices(): array
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
