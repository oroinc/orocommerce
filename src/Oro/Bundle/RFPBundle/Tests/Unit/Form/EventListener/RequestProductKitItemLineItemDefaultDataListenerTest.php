<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\FormBundle\Tests\Unit\Stub\FormTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\EventListener\RequestProductKitItemLineItemDefaultDataListener;
use Oro\Bundle\RFPBundle\ProductKit\Factory\RequestProductKitItemLineItemFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class RequestProductKitItemLineItemDefaultDataListenerTest extends TestCase
{
    private RequestProductKitItemLineItemFactory|MockObject $kitItemLineItemFactory;

    private RequestProductKitItemLineItemDefaultDataListener $listener;

    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->kitItemLineItemFactory = $this->createMock(RequestProductKitItemLineItemFactory::class);

        $this->listener = new RequestProductKitItemLineItemDefaultDataListener($this->kitItemLineItemFactory);

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
        $kitItemLineItem = (new RequestProductKitItemLineItem())
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
        $kitItemLineItem = (new RequestProductKitItemLineItem())
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

    public function testOnPreSetDataWhenNoDataAndHasKitItem(): void
    {
        $kitItem = new ProductKitItem();
        $product = new Product();
        $kitItemLineItem = (new RequestProductKitItemLineItem())
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
            (new RequestProductKitItemLineItem())->setProduct($product),
            $form->getData()
        );
    }

    public function testOnPreSetDataWhenNoDataAndNotRequiredAndHasKitItem(): void
    {
        $kitItem = new ProductKitItem();
        $kitItemLineItem = (new RequestProductKitItemLineItem())
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
            (new RequestProductKitItemLineItem())->setOptional(false)->setProduct(new Product()),
            $form->getData()
        );
    }
}
