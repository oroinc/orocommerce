<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Form\Type\ProductKitItemLineItemType;
use Oro\Bundle\ShoppingListBundle\Form\Type\ProductKitLineItemType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class ProductKitLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;
    use ProductKitItemLineItemTypeTrait;

    private ProductKitLineItemType $type;

    private Product $productKit;

    private Product $kitItemProduct1;

    private Product $kitItemProduct2;

    private ProductUnit $productUnitItem;

    private ProductUnit $productUnitEach;

    protected function setUp(): void
    {
        $this->type = new ProductKitLineItemType();

        $this->productKit = (new ProductStub())->setId(42);
        $this->kitItemProduct1 = (new ProductStub())->setId(142);
        $this->kitItemProduct2 = (new ProductStub())->setId(242);
        $this->productUnitItem = (new ProductUnit())->setCode('item');
        $this->productUnitEach = (new ProductUnit())->setCode('each');

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ProductKitLineItemType::class => $this->type,
                    ProductKitItemLineItemType::class => $this->createProductKitItemLineItemType(
                        $this,
                        [$this->productKit, $this->kitItemProduct1, $this->kitItemProduct2]
                    ),
                    QuantityType::class => $this->getQuantityType(),
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub(
                        ['item' => $this->productUnitItem, 'each' => $this->productUnitEach]
                    ),
                ],
                []
            ),
        ];
    }

    public function testBuildFormWhenNoLineItem(): void
    {
        $form = $this->factory->create(ProductKitLineItemType::class);

        $this->assertFormOptionEqual(LineItem::class, 'data_class', $form);

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormContainsField('unit', $form);
        $this->assertFormContainsField('kitItemLineItems', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(false, 'allow_add', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(false, 'allow_delete', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(ProductKitItemLineItemType::class, 'entry_type', $form->get('kitItemLineItems'));

        self::assertNull($form->getData());
        self::assertNull($form->get('quantity')->getData());
        self::assertNull($form->get('unit')->getData());
        self::assertNull($form->get('kitItemLineItems')->getData());
    }

    public function testBuildFormWhenHasLineItem(): void
    {
        $kitItem = new ProductKitItem();
        $kitItemLineItem = (new ProductKitItemLineItem())
            ->setProduct($this->kitItemProduct1)
            ->setKitItem($kitItem);
        $lineItem = (new LineItem())
            ->setProduct($this->productKit)
            ->setQuantity(42.1)
            ->setUnit($this->productUnitItem)
            ->addKitItemLineItem($kitItemLineItem);

        $form = $this->factory->create(ProductKitLineItemType::class, $lineItem);

        $this->assertFormOptionEqual(LineItem::class, 'data_class', $form);

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormContainsField('unit', $form);
        $this->assertFormContainsField('kitItemLineItems', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(false, 'allow_add', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(false, 'allow_delete', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(ProductKitItemLineItemType::class, 'entry_type', $form->get('kitItemLineItems'));

        self::assertSame($lineItem, $form->getData());
        self::assertSame($lineItem->getQuantity(), $form->get('quantity')->getData());
        self::assertSame($lineItem->getUnit(), $form->get('unit')->getData());
        self::assertEquals(new ArrayCollection([$kitItemLineItem]), $form->get('kitItemLineItems')->getData());
    }

    public function testSubmitWhenHasLineItem(): void
    {
        $kitItem = new ProductKitItem();
        $kitItemLineItem = (new ProductKitItemLineItem())
            ->setProduct($this->kitItemProduct1)
            ->setKitItem($kitItem);
        $lineItem = (new LineItem())
            ->setProduct($this->productKit)
            ->setQuantity(42.1)
            ->setUnit($this->productUnitItem)
            ->addKitItemLineItem($kitItemLineItem);

        $form = $this->factory->create(ProductKitLineItemType::class, $lineItem);

        $form->submit(
            [
                'quantity' => 42.2,
                'unit' => 'each',
                'kitItemLineItems' => [['product' => $this->kitItemProduct2->getId(), 'quantity' => 10.10]]
            ]
        );

        $this->assertFormIsValid($form);

        self::assertSame(42.2, $lineItem->getQuantity());
        self::assertEquals($this->productUnitEach, $lineItem->getUnit());
        self::assertEquals($this->kitItemProduct2, $kitItemLineItem->getProduct());
        self::assertEquals(10.10, $kitItemLineItem->getQuantity());
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_product_kit_line_item', $this->type->getBlockPrefix());
    }
}
