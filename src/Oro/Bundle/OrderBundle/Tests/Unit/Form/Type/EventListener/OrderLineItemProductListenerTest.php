<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderLineItemProductListener;
use Oro\Bundle\OrderBundle\Form\Type\OrderPriceType;
use Oro\Bundle\OrderBundle\Form\Type\OrderProductKitItemLineItemCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class OrderLineItemProductListenerTest extends TestCase
{
    private EntityStateChecker|MockObject $entityStateChecker;

    private OrderLineItemProductListener $listener;

    private FormFactoryInterface $formFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityStateChecker = $this->createMock(EntityStateChecker::class);

        $this->listener = new OrderLineItemProductListener($this->entityStateChecker);

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addType(new OrderProductKitItemLineItemCollectionType())
            ->getFormFactory();
    }

    public function testOnPostSetDataWhenHasData(): void
    {
        $product = (new Product())->setType(Product::TYPE_KIT);
        $orderLineItem = (new OrderLineItem())
            ->setProduct($product);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $orderLineItem)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', OrderProductKitItemLineItemCollectionType::class)
            ->add('price', OrderPriceType::class, ['hide_currency' => true, 'default_currency' => 'USD']);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertTrue($form->get('price')->getConfig()->getOption('readonly'));
        self::assertSame($product, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSetDataWhenNoData(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', OrderProductKitItemLineItemCollectionType::class)
            ->add('price', OrderPriceType::class, ['hide_currency' => true, 'default_currency' => 'USD']);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertFalse($form->get('price')->getConfig()->getOption('readonly'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSubmitWhenNoOrderLineItemAndNoSubmittedData(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', OrderProductKitItemLineItemCollectionType::class)
            ->add('price', OrderPriceType::class, ['hide_currency' => true, 'default_currency' => 'USD']);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertFalse($form->get('price')->getConfig()->getOption('readonly'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $form->submit([]);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertFalse($form->get('price')->getConfig()->getOption('readonly'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSubmitWhenNoOrderLineItemAndHasSubmittedProductKit(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', OrderProductKitItemLineItemCollectionType::class)
            ->add('price', OrderPriceType::class, ['hide_currency' => true, 'default_currency' => 'USD']);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $product = (new Product())->setType(Product::TYPE_KIT);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertFalse($form->get('price')->getConfig()->getOption('readonly'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $form->submit(['product' => $product]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertTrue($form->get('price')->getConfig()->getOption('readonly'));
        self::assertSame($product, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSubmitWhenNoOrderLineItemAndHasSubmittedSimpleProduct(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', OrderProductKitItemLineItemCollectionType::class)
            ->add('price', OrderPriceType::class, ['hide_currency' => true, 'default_currency' => 'USD']);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $product = (new Product())->setType(Product::TYPE_SIMPLE);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertFalse($form->get('price')->getConfig()->getOption('readonly'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $form->submit(['product' => $product]);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertFalse($form->get('price')->getConfig()->getOption('readonly'));
        self::assertSame($product, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSubmitWhenHasOrderLineItemAndNoOriginalProduct(): void
    {
        $productKit = (new Product())->setType(Product::TYPE_KIT);
        $orderLineItem = new OrderLineItem();

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $orderLineItem)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', OrderProductKitItemLineItemCollectionType::class)
            ->add('price', OrderPriceType::class, ['hide_currency' => true, 'default_currency' => 'USD']);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertFalse($form->get('price')->getConfig()->getOption('readonly'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $this->entityStateChecker
            ->expects(self::once())
            ->method('getOriginalEntityFieldData')
            ->with($orderLineItem, 'product')
            ->willReturn(null);

        $form->submit(['product' => $productKit]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertTrue($form->get('price')->getConfig()->getOption('readonly'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(new ArrayCollection(), $form->get('kitItemLineItems')->getConfig()->getOption('data'));
    }

    public function testOnPostSubmitWhenHasOrderLineItemAndProductChanged(): void
    {
        $productSimple = (new Product())->setType(Product::TYPE_SIMPLE);
        $productKit = (new Product())->setType(Product::TYPE_KIT);
        $orderLineItem = (new OrderLineItem())
            ->setProduct($productSimple);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $orderLineItem)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', OrderProductKitItemLineItemCollectionType::class)
            ->add('price', OrderPriceType::class, ['hide_currency' => true, 'default_currency' => 'USD']);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertFalse($form->get('price')->getConfig()->getOption('readonly'));
        self::assertSame($productSimple, $form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $this->entityStateChecker
            ->expects(self::once())
            ->method('getOriginalEntityFieldData')
            ->with($orderLineItem, 'product')
            ->willReturn($productSimple);

        $form->submit(['product' => $productKit]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertTrue($form->get('price')->getConfig()->getOption('readonly'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(new ArrayCollection(), $form->get('kitItemLineItems')->getConfig()->getOption('data'));
    }

    public function testOnPostSubmitWhenHasOrderLineItemAndProductNotChanged(): void
    {
        $productKit = (new Product())->setType(Product::TYPE_KIT);
        $orderLineItem = (new OrderLineItem())
            ->setProduct($productKit);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $orderLineItem)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', OrderProductKitItemLineItemCollectionType::class)
            ->add('price', OrderPriceType::class, ['hide_currency' => true, 'default_currency' => 'USD']);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertTrue($form->get('price')->getConfig()->getOption('readonly'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $this->entityStateChecker
            ->expects(self::once())
            ->method('getOriginalEntityFieldData')
            ->with($orderLineItem, 'product')
            ->willReturn($productKit);

        $form->submit(['product' => $productKit]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertTrue($form->get('price')->getConfig()->getOption('readonly'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('data'));
    }
}
