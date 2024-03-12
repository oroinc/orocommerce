<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderProductKitItemLineItemType;
use Oro\Bundle\OrderBundle\Tests\Unit\Stub\OrderProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

/**
 * Form type that represents a product kit item line item in an order line item.
 */
class OrderProductKitItemLineItemTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;
    use QuantityTypeTrait;
    use OrderProductKitItemLineItemTypeTrait;

    private Product $kitItemProduct1;

    private Product $kitItemProduct2;

    private Product $kitItemProduct3;

    private Product $kitItemProduct4;

    private ProductKitItem $kitItem1;

    private ProductKitItem $kitItem2;
    private array $kitItemProducts;

    private ProductUnit $productUnitItem;

    private ProductUnit $productUnitKg;

    private OrderProductKitItemLineItemType $formType;

    protected function setUp(): void
    {
        $this->productUnitItem = (new ProductUnit())->setCode('item');
        $this->productUnitKg = (new ProductUnit())->setCode('kg');
        $this->kitItemProduct1 = (new ProductStub())
            ->setId(300)
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($this->productUnitItem)->setPrecision(2));
        $this->kitItemProduct2 = (new ProductStub())->setId(301);
        $this->kitItemProduct3 = (new ProductStub())->setId(302);
        $this->kitItemProduct4 = (new ProductStub())->setId(303);
        $this->kitItem1 = (new ProductKitItemStub(400))
            ->setDefaultLabel('KitItem1')
            ->setMinimumQuantity(2)
            ->setMaximumQuantity(20)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct2))
            ->setProductUnit($this->productUnitItem);
        $this->kitItem2 = (new ProductKitItemStub(401))
            ->setDefaultLabel('KitItem2')
            ->setOptional(true)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct3))
            ->setProductUnit($this->productUnitKg);

        $this->kitItemProducts = [
            $this->kitItemProduct1->getId() => $this->kitItemProduct1,
            $this->kitItemProduct2->getId() => $this->kitItemProduct2,
            $this->kitItemProduct3->getId() => $this->kitItemProduct3,
            $this->kitItemProduct4->getId() => $this->kitItemProduct4,
        ];

        $this->formType = $this->createOrderProductKitItemLineItemType($this, array_values($this->kitItemProducts));

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        $this->formType,
                        $this->getQuantityType(),
                        Select2EntityType::class => new EntityTypeStub($this->kitItemProducts),
                    ],
                    []
                ),
            ]
        );
    }

    public function testBuildFormWhenNoDataAndRequiredKitItem(): void
    {
        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            null,
            ['product_kit_item' => $this->kitItem1, 'currency' => 'USD', 'required' => true]
        );

        $this->assertFormOptionEqual(OrderProductKitItemLineItem::class, 'data_class', $form);

        $this->assertFormContainsField('product', $form);
        self::assertEquals(
            array_values($this->kitItemProducts),
            $form->get('product')->getConfig()->getOption('choices')
        );
        $this->assertFormOptionEqual(true, 'required', $form->get('product'));

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('quantity'));

        $this->assertFormContainsField('price', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('price'));
        $this->assertFormOptionEqual(true, 'hide_currency', $form->get('price'));
        $this->assertFormOptionEqual('USD', 'default_currency', $form->get('price'));

        self::assertEquals(
            (new OrderProductKitItemLineItem())
                ->setKitItem($this->kitItem1)
                ->setProduct($this->kitItemProduct1)
                ->setProductUnit($this->kitItem1->getProductUnit())
                ->setProductUnitPrecision(2)
                ->setQuantity(2.0),
            $form->getData()
        );

        $formView = $form->createView();

        self::assertSame($this->kitItem1, $formView->vars['product_kit_item']);
        self::assertSame($this->kitItem1->getDefaultLabel()->getString(), $formView->vars['label']);
        self::assertSame($this->kitItem1->isOptional(), $formView->vars['is_optional']);
        self::assertSame($this->kitItem1->getProductUnit()->getCode(), $formView->vars['unit_code']);
        self::assertSame($this->kitItem1->getMinimumQuantity(), $formView->vars['minimum_quantity']);
        self::assertSame($this->kitItem1->getMaximumQuantity(), $formView->vars['maximum_quantity']);
        self::assertSame(2, $formView->vars['unit_precision']);
    }

    public function testBuildFormWhenNoDataAndOptionalKitItem(): void
    {
        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            null,
            ['product_kit_item' => $this->kitItem2, 'currency' => 'USD', 'required' => false]
        );

        $this->assertFormOptionEqual(OrderProductKitItemLineItem::class, 'data_class', $form);

        $this->assertFormContainsField('product', $form);
        self::assertEquals(
            array_values($this->kitItemProducts),
            $form->get('product')->getConfig()->getOption('choices')
        );
        $this->assertFormOptionEqual(false, 'required', $form->get('product'));

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('quantity'));

        $this->assertFormContainsField('price', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('price'));
        $this->assertFormOptionEqual(true, 'hide_currency', $form->get('price'));
        $this->assertFormOptionEqual('USD', 'default_currency', $form->get('price'));


        self::assertEquals(
            (new OrderProductKitItemLineItem())
                ->setKitItem($this->kitItem2)
                ->setProductUnit($this->kitItem2->getProductUnit())
                ->setQuantity(1.0),
            $form->getData()
        );

        $formView = $form->createView();

        self::assertSame($this->kitItem2, $formView->vars['product_kit_item']);
        self::assertSame($this->kitItem2->getDefaultLabel()->getString(), $formView->vars['label']);
        self::assertSame($this->kitItem2->isOptional(), $formView->vars['is_optional']);
        self::assertSame($this->kitItem2->getProductUnit()->getCode(), $formView->vars['unit_code']);
        self::assertSame($this->kitItem2->getMinimumQuantity(), $formView->vars['minimum_quantity']);
        self::assertSame($this->kitItem2->getMaximumQuantity(), $formView->vars['maximum_quantity']);
        self::assertSame(0, $formView->vars['unit_precision']);
    }

    public function testBuildFormWhenHasDataAndOptionalKitItem(): void
    {
        $kitItemLineItem = new OrderProductKitItemLineItem();

        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            $kitItemLineItem,
            ['product_kit_item' => $this->kitItem2, 'currency' => 'USD', 'required' => false]
        );

        $this->assertFormOptionEqual(OrderProductKitItemLineItem::class, 'data_class', $form);

        $this->assertFormContainsField('product', $form);
        self::assertEquals(
            array_values($this->kitItemProducts),
            $form->get('product')->getConfig()->getOption('choices')
        );
        $this->assertFormOptionEqual(false, 'required', $form->get('product'));

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('quantity'));
        $this->assertFormOptionEqual(true, 'disabled', $form->get('quantity'));

        $this->assertFormContainsField('price', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('price'));
        $this->assertFormOptionEqual(true, 'hide_currency', $form->get('price'));
        $this->assertFormOptionEqual('USD', 'default_currency', $form->get('price'));
        $this->assertFormOptionEqual(true, 'disabled', $form->get('price')->get('value'));

        self::assertSame($kitItemLineItem, $form->getData());

        $formView = $form->createView();

        self::assertSame($this->kitItem2, $formView->vars['product_kit_item']);
    }

    public function testBuildFormWhenHasDataAndRequiredKitItem(): void
    {
        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setKitItem($this->kitItem1)
            ->setProduct($this->kitItemProduct2)
            ->setQuantity(12.3456);

        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            $kitItemLineItem,
            ['product_kit_item' => $this->kitItem1, 'currency' => 'USD', 'required' => true]
        );

        $this->assertFormOptionEqual(OrderProductKitItemLineItem::class, 'data_class', $form);

        $this->assertFormContainsField('product', $form);
        self::assertEquals(
            array_values($this->kitItemProducts),
            $form->get('product')->getConfig()->getOption('choices')
        );
        $this->assertFormOptionEqual(true, 'required', $form->get('product'));

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('quantity'));

        $this->assertFormContainsField('price', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('price'));
        $this->assertFormOptionEqual(true, 'hide_currency', $form->get('price'));
        $this->assertFormOptionEqual('USD', 'default_currency', $form->get('price'));
        $this->assertFormOptionEqual(false, 'disabled', $form->get('price')->get('value'));

        self::assertSame($kitItemLineItem, $form->getData());

        $formView = $form->createView();

        self::assertSame($this->kitItem1, $formView->vars['product_kit_item']);
    }

    public function testBuildFormWhenProductNotPresent(): void
    {
        $notPresentProduct = (new ProductStub())
            ->setId(42)
            ->setSku('SKU42')
            ->setDefaultName('Product42');

        $kitItemLineItem = (new OrderProductKitItemLineItemStub(999))
            ->setKitItem($this->kitItem1)
            ->setProduct($notPresentProduct)
            ->setQuantity(12.3456);

        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            $kitItemLineItem,
            ['product_kit_item' => $this->kitItem1, 'currency' => 'USD', 'required' => true]
        );

        $this->assertFormOptionEqual(OrderProductKitItemLineItem::class, 'data_class', $form);

        $this->assertFormContainsField('product', $form);
        self::assertEquals(
            array_values(array_merge([$notPresentProduct], $this->kitItemProducts)),
            $form->get('product')->getConfig()->getOption('choices')
        );
        $this->assertFormOptionEqual(true, 'required', $form->get('product'));
        self::assertEquals($notPresentProduct, $form->get('product')->getConfig()->getOption('data'));

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('quantity'));

        $this->assertFormContainsField('price', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('price'));
        $this->assertFormOptionEqual(true, 'hide_currency', $form->get('price'));
        $this->assertFormOptionEqual('USD', 'default_currency', $form->get('price'));
        $this->assertFormOptionEqual(false, 'disabled', $form->get('price')->get('value'));

        self::assertSame($kitItemLineItem, $form->getData());

        $formView = $form->createView();

        self::assertSame($this->kitItem1, $formView->vars['product_kit_item']);

        self::assertEquals(
            ['data-ghost-option' => true, 'class' => 'ghost-option'],
            $formView['product']->vars['choices'][0]->attr
        );
    }

    public function testBuildFormWhenProductIsNull(): void
    {
        $kitItemLineItem = (new OrderProductKitItemLineItemStub(999))
            ->setKitItem($this->kitItem1)
            ->setProductSku('GP')
            ->setProductName('Ghost Product')
            ->setQuantity(12.3456);

        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            $kitItemLineItem,
            ['product_kit_item' => $this->kitItem1, 'currency' => 'USD', 'required' => true]
        );

        $this->assertFormOptionEqual(OrderProductKitItemLineItem::class, 'data_class', $form);

        $this->assertFormContainsField('product', $form);
        self::assertCount(5, $form->get('product')->getConfig()->getOption('choices'));
        $this->assertFormOptionEqual(true, 'required', $form->get('product'));
        self::assertNotNull($form->get('product')->getConfig()->getOption('data'));

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('quantity'));

        $this->assertFormContainsField('price', $form);
        $this->assertFormOptionEqual(true, 'required', $form->get('price'));
        $this->assertFormOptionEqual(true, 'hide_currency', $form->get('price'));
        $this->assertFormOptionEqual('USD', 'default_currency', $form->get('price'));
        $this->assertFormOptionEqual(false, 'disabled', $form->get('price')->get('value'));

        self::assertSame($kitItemLineItem, $form->getData());

        $formView = $form->createView();

        self::assertSame($this->kitItem1, $formView->vars['product_kit_item']);

        self::assertEquals(
            ['data-ghost-option' => true, 'class' => 'ghost-option'],
            $formView['product']->vars['choices'][0]->attr
        );
    }

    public function testSubmitWhenNoData(): void
    {
        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            null,
            ['product_kit_item' => $this->kitItem1, 'currency' => 'USD', 'required' => true]
        );

        $data = [
            'product' => $this->kitItemProduct2->getId(),
            'quantity' => 1.2345,
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            (new OrderProductKitItemLineItem())
                ->setKitItem($this->kitItem1)
                ->setProduct($this->kitItemProduct2)
                ->setProductUnit($this->kitItem1->getProductUnit())
                ->setQuantity(1.2345),
            $form->getData()
        );
    }

    public function testSubmitWhenHasData(): void
    {
        $kitItemLineItem = (new OrderProductKitItemLineItem())
            ->setKitItem($this->kitItem1)
            ->setProduct($this->kitItemProduct2)
            ->setQuantity(1.2345);

        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            $kitItemLineItem,
            ['product_kit_item' => $this->kitItem1, 'currency' => 'USD', 'required' => true]
        );

        $data = [
            'product' => $this->kitItemProduct1->getId(),
            'quantity' => 2.3456,
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            (new OrderProductKitItemLineItem())
                ->setKitItem($this->kitItem1)
                ->setProduct($this->kitItemProduct1)
                ->setQuantity(2.3456),
            $form->getData()
        );
    }

    public function testSubmitWhenProductNotPresent(): void
    {
        $notPresentProduct = (new ProductStub())
            ->setId(42)
            ->setSku('SKU42')
            ->setDefaultName('Product42');

        $kitItemLineItem = (new OrderProductKitItemLineItemStub(999))
            ->setKitItem($this->kitItem1)
            ->setProduct($notPresentProduct)
            ->setQuantity(1.2345);

        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            $kitItemLineItem,
            ['product_kit_item' => $this->kitItem1, 'currency' => 'USD', 'required' => true]
        );

        $data = [
            'product' => $notPresentProduct->getId(),
            'quantity' => 2.3456,
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            (new OrderProductKitItemLineItemStub(999))
                ->setKitItem($this->kitItem1)
                ->setProduct($notPresentProduct)
                ->setQuantity(2.3456),
            $form->getData()
        );
    }

    public function testSubmitWhenProductIsNull(): void
    {
        $kitItemLineItem = (new OrderProductKitItemLineItemStub(999))
            ->setKitItem($this->kitItem1)
            ->setProductSku('GP1')
            ->setProductName('Ghost Product')
            ->setQuantity(1.2345);

        $form = $this->factory->create(
            OrderProductKitItemLineItemType::class,
            $kitItemLineItem,
            ['product_kit_item' => $this->kitItem1, 'currency' => 'USD', 'required' => true]
        );

        $data = [
            'product' => PHP_INT_MIN,
            'quantity' => 2.3456,
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            (new OrderProductKitItemLineItemStub(999))
                ->setKitItem($this->kitItem1)
                ->setProductSku('GP1')
                ->setProductName('Ghost Product')
                ->setQuantity(2.3456),
            $form->getData()
        );
    }
}
