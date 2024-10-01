<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Oro\Bundle\FormBundle\Tests\Unit\Stub\FormTypeStub;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderProductKitItemLineItemDefaultDataListener;
use Oro\Bundle\OrderBundle\ProductKit\Factory\OrderProductKitItemLineItemFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class OrderProductKitItemLineItemDefaultDataListenerTest extends TestCase
{
    private OrderProductKitItemLineItemFactory|MockObject $kitItemLineItemFactory;

    private OrderProductKitItemLineItemDefaultDataListener $listener;

    private FormFactoryInterface $formFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->kitItemLineItemFactory = $this->createMock(OrderProductKitItemLineItemFactory::class);

        $this->listener = new OrderProductKitItemLineItemDefaultDataListener($this->kitItemLineItemFactory);

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addType(new FormTypeStub(['product_kit_item']))
            ->getFormFactory();
    }

    public function testOnPreSetDataWhenHasData(): void
    {
        $this->kitItemLineItemFactory
            ->expects(self::never())
            ->method(self::anything());

        $kitItemLineItem = new OrderProductKitItemLineItem();
        $formBuilder = $this->formFactory
            ->createBuilder(FormTypeStub::class, $kitItemLineItem)
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertSame($kitItemLineItem, $form->getData());
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
        $kitItemLineItem = (new OrderProductKitItemLineItem())
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
            (new OrderProductKitItemLineItem())->setOptional(false)->setProduct($product),
            $form->getData()
        );
    }

    public function testOnPreSetDataWhenNoDataAndNotRequiredAndHasKitItem(): void
    {
        $kitItem = new ProductKitItem();
        $kitItemLineItem = (new OrderProductKitItemLineItem())
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
            (new OrderProductKitItemLineItem())->setOptional(true)->setProduct(null),
            $form->getData()
        );
    }
}
