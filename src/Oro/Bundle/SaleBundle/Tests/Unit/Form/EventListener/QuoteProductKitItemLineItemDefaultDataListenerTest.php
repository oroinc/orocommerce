<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\FormBundle\Tests\Unit\Stub\FormTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteProductKitItemLineItemDefaultDataListener;
use Oro\Bundle\SaleBundle\ProductKit\Factory\QuoteProductKitItemLineItemFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class QuoteProductKitItemLineItemDefaultDataListenerTest extends TestCase
{
    private QuoteProductKitItemLineItemFactory|MockObject $kitItemLineItemFactory;

    private QuoteProductKitItemLineItemDefaultDataListener $listener;

    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->kitItemLineItemFactory = $this->createMock(QuoteProductKitItemLineItemFactory::class);

        $this->listener = new QuoteProductKitItemLineItemDefaultDataListener($this->kitItemLineItemFactory);

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addType(new FormTypeStub(['product_kit_item']))
            ->getFormFactory();
    }

    public function testOnPreSetDataWhenHasDataAndProductUnitIsActual(): void
    {
        $this->kitItemLineItemFactory
            ->expects(self::never())
            ->method(self::anything());

        $productUnit = (new ProductUnit())->setCode('each');
        $kitItem = (new ProductKitItemStub(1))
            ->setProductUnit($productUnit);
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setProductUnit($productUnit);

        $formBuilder = $this->formFactory
            ->createBuilder(FormTypeStub::class, $kitItemLineItem)
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertSame($kitItemLineItem, $form->getData());
        self::assertEquals($productUnit->getCode(), $kitItemLineItem->getProductUnit()->getCode());
        self::assertEquals($productUnit->getCode(), $kitItemLineItem->getProductUnitCode());
    }

    public function testOnPreSetDataWhenHasDataAndProductUnitIsNotActual(): void
    {
        $this->kitItemLineItemFactory
            ->expects(self::never())
            ->method(self::anything());

        $kitItemProductUnit = (new ProductUnit())->setCode('each');
        $kitItem = (new ProductKitItemStub(1))
            ->setProductUnit($kitItemProductUnit);

        $kitItemLineItemProductUnit = (new ProductUnit())->setCode('piece');
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setKitItem($kitItem)
            ->setProductUnit($kitItemLineItemProductUnit);

        $formBuilder = $this->formFactory
            ->createBuilder(FormTypeStub::class, $kitItemLineItem)
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertSame($kitItemLineItem, $form->getData());
        self::assertEquals($kitItemProductUnit->getCode(), $kitItemLineItem->getProductUnit()->getCode());
        self::assertEquals($kitItemProductUnit->getCode(), $kitItemLineItem->getProductUnitCode());
    }

    public function testOnPreSetDataWhenNoDataAndNoKitItem(): void
    {
        $this->kitItemLineItemFactory
            ->expects(self::never())
            ->method(self::anything());

        $formBuilder = $this->formFactory
            ->createBuilder(FormTypeStub::class)
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertNull($form->getData());
    }

    public function testOnPreSetDataWhenNoDataAndRequiredAndHasKitItem(): void
    {
        $kitItem = new ProductKitItem();
        $product = new Product();
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setOptional(false)
            ->setProduct($product);
        $this->kitItemLineItemFactory
            ->expects(self::once())
            ->method('createKitItemLineItem')
            ->with($kitItem)
            ->willReturn($kitItemLineItem);

        $formBuilder = $this->formFactory
            ->createBuilder(FormTypeStub::class, null, ['required' => true, 'product_kit_item' => $kitItem])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertSame($kitItemLineItem, $form->getData());
        self::assertEquals(
            (new QuoteProductKitItemLineItem())->setOptional(false)->setProduct($product),
            $form->getData()
        );
    }

    public function testOnPreSetDataWhenNoDataAndNotRequiredAndHasKitItem(): void
    {
        $kitItem = new ProductKitItem();
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setOptional(false)
            ->setProduct(new Product());

        $this->kitItemLineItemFactory
            ->expects(self::once())
            ->method('createKitItemLineItem')
            ->with($kitItem)
            ->willReturn($kitItemLineItem);

        $formBuilder = $this->formFactory
            ->createBuilder(FormTypeStub::class, null, ['required' => false, 'product_kit_item' => $kitItem])
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertSame($kitItemLineItem, $form->getData());
        self::assertEquals(
            (new QuoteProductKitItemLineItem())->setOptional(false)->setProduct(new Product()),
            $form->getData()
        );
    }
}
