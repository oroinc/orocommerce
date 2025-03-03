<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderDTO;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactory;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Form\Type\ProductKitItemLineItemType;
use Oro\Bundle\ShoppingListBundle\Form\Type\ProductKitLineItemType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;

class ProductKitLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;
    use ProductKitItemLineItemTypeTrait;

    private FrontendProductPricesDataProvider&MockObject $frontendProductPricesDataProvider;
    private SubtotalProviderInterface&MockObject $lineItemNotPricedSubtotalProvider;
    private ProductKitLineItemType $type;
    private Product $productKit;
    private Product $kitItemProduct1;
    private Product $kitItemProduct2;
    private ProductUnit $productUnitItem;
    private ProductUnit $productUnitEach;

    #[\Override]
    protected function setUp(): void
    {
        $this->lineItemNotPricedSubtotalProvider = $this->createMock(SubtotalProviderInterface::class);
        $this->frontendProductPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->type = new ProductKitLineItemType(
            $this->frontendProductPricesDataProvider,
            new ProductLineItemsHolderFactory(),
            $this->lineItemNotPricedSubtotalProvider
        );

        $this->productKit = (new ProductStub())->setId(42);
        $this->kitItemProduct1 = (new ProductStub())->setId(142);
        $this->kitItemProduct2 = (new ProductStub())->setId(242);
        $this->productUnitItem = (new ProductUnit())->setCode('item');
        $this->productUnitEach = (new ProductUnit())->setCode('each');

        parent::setUp();
    }

    #[\Override]
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
                    )
                ],
                []
            )
        ];
    }

    private function getProductKitItem(array $kitItemProducts): ProductKitItem
    {
        $productKitItem = new ProductKitItem();
        foreach ($kitItemProducts as $kitItemProduct) {
            $productKitItem->addKitItemProduct($kitItemProduct);
        }

        return $productKitItem;
    }

    private function getProductKitItemProduct(Product $product): ProductKitItemProduct
    {
        $kitItemProduct = new ProductKitItemProduct();
        $kitItemProduct->setProduct($product);

        return $kitItemProduct;
    }

    private function getProductKitItemLineItem(
        ProductKitItem $kitItem,
        ?Product $product = null,
        ?int $sortOrder = null
    ): ProductKitItemLineItem {
        $kitItemLineItem = new ProductKitItemLineItem();
        $kitItemLineItem->setKitItem($kitItem);
        if (null !== $product) {
            $kitItemLineItem->setProduct($product);
        }
        if (null !== $sortOrder) {
            $kitItemLineItem->setSortOrder($sortOrder);
        }

        return $kitItemLineItem;
    }

    public function testBuildFormWhenNoLineItem(): void
    {
        $form = $this->factory->create(ProductKitLineItemType::class);

        $this->assertFormOptionEqual(LineItem::class, 'data_class', $form);

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual([], 'constraints', $form->get('quantity'));
        $this->assertFormContainsField('unit', $form);
        $this->assertFormContainsField('kitItemLineItems', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(false, 'allow_add', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(false, 'allow_delete', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(ProductKitItemLineItemType::class, 'entry_type', $form->get('kitItemLineItems'));

        self::assertNull($form->getData());
        self::assertNull($form->get('quantity')->getData());
        self::assertNull($form->get('unit')->getData());
        self::assertNull($form->get('notes')->getData());
        self::assertNull($form->get('kitItemLineItems')->getData());

        $formView = $form->createView();

        self::assertArrayHasKey('productPrices', $formView['kitItemLineItems']->vars);
        self::assertEquals([], $formView['kitItemLineItems']->vars['productPrices']);

        self::assertArrayHasKey('subtotal', $formView->vars);
        self::assertNull($formView->vars['subtotal']);
    }

    public function testBuildFormWhenHasLineItem(): void
    {
        $kitItem = $this->getProductKitItem([
            $this->getProductKitItemProduct($this->kitItemProduct1),
            $this->getProductKitItemProduct($this->kitItemProduct2)
        ]);
        $kitItemLineItem = $this->getProductKitItemLineItem($kitItem, $this->kitItemProduct1);
        $lineItem = new LineItem();
        $lineItem->setProduct($this->productKit);
        $lineItem->setQuantity(42.1);
        $lineItem->setUnit($this->productUnitItem);
        $lineItem->addKitItemLineItem($kitItemLineItem);
        $lineItem->setNotes('sample notes');

        $productPrices = [
            $this->productKit->getId() => ['sample_key1' => 'sample_value1'],
            $this->kitItemProduct1->getId() => ['sample_key2' => 'sample_value2'],
            $this->kitItemProduct2->getId() => ['sample_key3' => 'sample_value3']
        ];
        $this->frontendProductPricesDataProvider->expects(self::once())
            ->method('getAllPricesForProducts')
            ->with([$this->productKit, $this->kitItemProduct1, $this->kitItemProduct2])
            ->willReturn($productPrices);

        $subtotal = new Subtotal();
        $this->lineItemNotPricedSubtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->with((new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection([$lineItem])))
            ->willReturn($subtotal);

        $form = $this->factory->create(ProductKitLineItemType::class, $lineItem);

        $this->assertFormOptionEqual(LineItem::class, 'data_class', $form);

        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual([], 'constraints', $form->get('quantity'));
        $this->assertFormContainsField('unit', $form);
        $this->assertFormContainsField('kitItemLineItems', $form);
        $this->assertFormContainsField('notes', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(false, 'allow_add', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(false, 'allow_delete', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(ProductKitItemLineItemType::class, 'entry_type', $form->get('kitItemLineItems'));

        self::assertSame($lineItem, $form->getData());
        self::assertSame($lineItem->getQuantity(), $form->get('quantity')->getData());
        self::assertSame($lineItem->getUnit(), $form->get('unit')->getData());
        self::assertSame($lineItem->getNotes(), $form->get('notes')->getData());
        self::assertEquals(new ArrayCollection([$kitItemLineItem]), $form->get('kitItemLineItems')->getData());

        $formView = $form->createView();

        self::assertArrayHasKey('productPrices', $formView['kitItemLineItems']->vars);
        self::assertEquals($productPrices, $formView['kitItemLineItems']->vars['productPrices']);

        self::assertArrayHasKey('subtotal', $formView->vars);
        self::assertSame($subtotal, $formView->vars['subtotal']);
    }

    public function testBuildFormSortedKitItems(): void
    {
        $kitItem1 = $this->getProductKitItem([
            $this->getProductKitItemProduct($this->kitItemProduct1),
            $this->getProductKitItemProduct($this->kitItemProduct2)
        ]);
        $kitItem1->setSortOrder(3);
        $kitItem2 = $this->getProductKitItem([
            $this->getProductKitItemProduct($this->kitItemProduct1)
        ]);
        $kitItem2->setSortOrder(2);
        $kitItem2->setOptional(true);
        $kitItemLineItem1 = $this->getProductKitItemLineItem($kitItem1, $this->kitItemProduct1, 1);
        $kitItemLineItem2 = $this->getProductKitItemLineItem($kitItem2);
        $lineItem = new LineItem();
        $lineItem->setProduct($this->productKit);
        $lineItem->setQuantity(42.1);
        $lineItem->setUnit($this->productUnitItem);
        $lineItem->addKitItemLineItem($kitItemLineItem1);
        $lineItem->addKitItemLineItem($kitItemLineItem2);

        $productPrices = [
            $this->productKit->getId() => ['sample_key1' => 'sample_value1'],
            $this->kitItemProduct1->getId() => ['sample_key2' => 'sample_value2'],
            $this->kitItemProduct2->getId() => ['sample_key3' => 'sample_value3']
        ];
        $this->frontendProductPricesDataProvider->expects(self::once())
            ->method('getAllPricesForProducts')
            ->with([$this->productKit, $this->kitItemProduct1, $this->kitItemProduct2, $this->kitItemProduct1])
            ->willReturn($productPrices);

        $form = $this->factory->create(ProductKitLineItemType::class, $lineItem);

        self::assertSame($lineItem, $form->getData());
        self::assertEquals(
            new ArrayCollection([$kitItemLineItem1, $kitItemLineItem2]),
            $form->get('kitItemLineItems')->getData()
        );

        $formView = $form->createView();

        $kitItemLineItemsFormViews = $formView['kitItemLineItems']->children;
        self::assertEquals($kitItemLineItem2, $kitItemLineItemsFormViews[0]->vars['data']);
        self::assertEquals($kitItemLineItem1, $kitItemLineItemsFormViews[1]->vars['data']);
    }

    public function testSubmitWhenHasLineItem(): void
    {
        $kitItem = $this->getProductKitItem([
            $this->getProductKitItemProduct($this->kitItemProduct1),
            $this->getProductKitItemProduct($this->kitItemProduct2)
        ]);
        $kitItemLineItem = $this->getProductKitItemLineItem($kitItem, $this->kitItemProduct1);
        $lineItem = new LineItem();
        $lineItem->setProduct($this->productKit);
        $lineItem->setQuantity(42.1);
        $lineItem->setUnit($this->productUnitItem);
        $lineItem->addKitItemLineItem($kitItemLineItem);

        $form = $this->factory->create(ProductKitLineItemType::class, $lineItem);

        $notes = 'sample notes';
        $form->submit(
            [
                'quantity' => 42.2,
                'unit' => 'each',
                'kitItemLineItems' => [['product' => $this->kitItemProduct2->getId(), 'quantity' => 10.10]],
                'notes' => $notes
            ]
        );

        $this->assertFormIsValid($form);

        self::assertSame(42.2, $lineItem->getQuantity());
        self::assertEquals($this->productUnitEach, $lineItem->getUnit());
        self::assertEquals($notes, $lineItem->getNotes());
        self::assertEquals($this->kitItemProduct2, $kitItemLineItem->getProduct());
        self::assertEquals(10.10, $kitItemLineItem->getQuantity());
    }

    public function testSubmitWhenHasLineItemWithOptionalKitItem(): void
    {
        $kitItem1 = $this->getProductKitItem([
            $this->getProductKitItemProduct($this->kitItemProduct1),
            $this->getProductKitItemProduct($this->kitItemProduct2)
        ]);
        $kitItem2 = $this->getProductKitItem([
            $this->getProductKitItemProduct($this->kitItemProduct1)
        ]);
        $kitItem2->setOptional(true);
        $kitItemLineItem1 = $this->getProductKitItemLineItem($kitItem1, $this->kitItemProduct1);
        $kitItemLineItem2 = $this->getProductKitItemLineItem($kitItem2);
        $lineItem = new LineItem();
        $lineItem->setProduct($this->productKit);
        $lineItem->setQuantity(42.1);
        $lineItem->setUnit($this->productUnitItem);
        $lineItem->addKitItemLineItem($kitItemLineItem1);
        $lineItem->addKitItemLineItem($kitItemLineItem2);

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
        self::assertCount(
            1,
            $lineItem->getKitItemLineItems(),
            'The optional kit item line item without selected product was expected to be excluded'
        );
        self::assertSame($kitItemLineItem1, $lineItem->getKitItemLineItems()->first());
        self::assertEquals($this->productUnitEach, $lineItem->getUnit());
        self::assertEquals($this->kitItemProduct2, $kitItemLineItem1->getProduct());
        self::assertEquals(10.10, $kitItemLineItem1->getQuantity());
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_product_kit_line_item', $this->type->getBlockPrefix());
    }
}
