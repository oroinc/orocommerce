<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Form\Type\ProductKitItemLineItemType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class ProductKitItemLineItemTypeTest extends FormIntegrationTestCase
{
    use ProductKitItemLineItemTypeTrait;
    use QuantityTypeTrait;

    private Product $kitItemProduct1;

    private Product $kitItemProduct2;

    protected function setUp(): void
    {
        $this->kitItemProduct1 = (new ProductStub())->setId(142);
        $this->kitItemProduct2 = (new ProductStub())->setId(142);

        $this->type = $this->createProductKitItemLineItemType($this, [$this->kitItemProduct1, $this->kitItemProduct2]);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ProductKitItemLineItemType::class => $this->type,
                    QuantityType::class => $this->getQuantityType(),
                ],
                []
            ),
        ];
    }

    public function testBuildFormWhenNoKitItemLineItem(): void
    {
        $form = $this->factory->create(ProductKitItemLineItemType::class);

        $this->assertFormOptionEqual(ProductKitItemLineItem::class, 'data_class', $form);

        $this->assertFormContainsField('product', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('product'));
        $this->assertFormOptionEqual(true, 'expanded', $form->get('product'));
        $this->assertFormOptionEqual(false, 'multiple', $form->get('product'));
        $this->assertFormOptionEqual([], 'choices', $form->get('product'));

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('quantity'));
        $this->assertFormOptionEqual(true, 'useInputTypeNumberValueFormat', $form->get('quantity'));

        self::assertNull($form->getData());
        self::assertNull($form->get('product')->getData());
        self::assertNull($form->get('quantity')->getData());
    }

    public function testBuildFormWhenKitItemLineItemIsNotOptional(): void
    {
        $kitItem = (new ProductKitItem())
            ->setOptional(true)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct1));
        $kitItemLineItem = (new ProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setProduct($this->kitItemProduct1)
            ->setQuantity(1.42);

        $form = $this->factory->create(ProductKitItemLineItemType::class, $kitItemLineItem);

        $this->assertFormOptionEqual(ProductKitItemLineItem::class, 'data_class', $form);

        $this->assertFormContainsField('product', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('product'));
        $this->assertFormOptionEqual(true, 'expanded', $form->get('product'));
        $this->assertFormOptionEqual(false, 'multiple', $form->get('product'));
        $this->assertFormOptionEqual(
            [$this->kitItemProduct1, $this->kitItemProduct2, null],
            'choices',
            $form->get('product')
        );

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('quantity'));
        $this->assertFormOptionEqual(true, 'useInputTypeNumberValueFormat', $form->get('quantity'));

        self::assertSame($kitItemLineItem, $form->getData());
        self::assertEquals($this->kitItemProduct1, $form->get('product')->getData());
        self::assertEquals(1.42, $form->get('quantity')->getData());
    }

    public function testBuildFormWhenKitItemLineItemIsOptional(): void
    {
        $kitItem = (new ProductKitItem())
            ->setOptional(false)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct1));
        $kitItemLineItem = (new ProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setProduct($this->kitItemProduct1)
            ->setQuantity(1.42);

        $form = $this->factory->create(ProductKitItemLineItemType::class, $kitItemLineItem);

        $this->assertFormOptionEqual(ProductKitItemLineItem::class, 'data_class', $form);

        $this->assertFormContainsField('product', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('product'));
        $this->assertFormOptionEqual(true, 'expanded', $form->get('product'));
        $this->assertFormOptionEqual(false, 'multiple', $form->get('product'));
        $this->assertFormOptionEqual(
            [$this->kitItemProduct1, $this->kitItemProduct2],
            'choices',
            $form->get('product')
        );

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('quantity'));
        $this->assertFormOptionEqual(true, 'useInputTypeNumberValueFormat', $form->get('quantity'));

        self::assertSame($kitItemLineItem, $form->getData());
        self::assertEquals($this->kitItemProduct1, $form->get('product')->getData());
        self::assertEquals(1.42, $form->get('quantity')->getData());
    }

    public function testSubmitWhenNoInitialData(): void
    {
        $form = $this->factory->create(ProductKitItemLineItemType::class);

        self::assertNull($form->getData());

        $form->submit([]);

        $this->assertFormIsValid($form);

        self::assertEquals(new ProductKitItemLineItem(), $form->getData());
    }

    /**
     * @dataProvider submitWhenHasInitialData
     */
    public function testSubmitWhenHasInitialData(
        array $submittedData,
        bool $isOptional,
        ProductKitItemLineItem $expected
    ): void {
        $kitItem = (new ProductKitItem())
            ->setOptional(true)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct1));
        $kitItemLineItem = (new ProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setProduct($this->kitItemProduct1)
            ->setQuantity(1.42);

        $form = $this->factory->create(ProductKitItemLineItemType::class, $kitItemLineItem);

        self::assertSame($kitItemLineItem, $form->getData());

        $form->submit($submittedData);

        $this->assertFormIsValid($form);

        $expected->setKitItem($kitItem);
        self::assertEquals($expected, $form->getData());
    }

    public function submitWhenHasInitialData(): array
    {
        return [
            'with empty data' => [
                'submittedData' => [],
                'isOptional' => true,
                'expected' => new ProductKitItemLineItem(),
            ],
            'with empty data and optional kitItem' => [
                'submittedData' => [],
                'isOptional' => false,
                'expected' => new ProductKitItemLineItem(),
            ],
            'with another product' => [
                'submittedData' => ['product' => [142 => 142]],
                'isOptional' => true,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct((new ProductStub())->setId(142))
                    ->setQuantity(null),
            ],
            'with another product and optional kitItem' => [
                'submittedData' => ['product' => [142 => 142]],
                'isOptional' => true,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct((new ProductStub())->setId(142))
                    ->setQuantity(null),
            ],
            'with another quantity' => [
                'submittedData' => ['quantity' => 2.42],
                'isOptional' => false,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct(null)
                    ->setQuantity(2.42),
            ],

            'with another quantity and optional kitItem' => [
                'submittedData' => ['quantity' => 2.42],
                'isOptional' => true,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct(null)
                    ->setQuantity(2.42),
            ],
            'with another product and quantity' => [
                'submittedData' => ['product' => [142 => 142], 'quantity' => 2.42],
                'isOptional' => false,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct((new ProductStub())->setId(142))
                    ->setQuantity(2.42),
            ],
            'with another product and quantity and optional kitItem' => [
                'submittedData' => ['product' => [142 => 142], 'quantity' => 2.42],
                'isOptional' => true,
                'expected' => (new ProductKitItemLineItem())
                    ->setProduct((new ProductStub())->setId(142))
                    ->setQuantity(2.42),
            ],
        ];
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_product_kit_item_line_item', $this->type->getBlockPrefix());
    }
}
