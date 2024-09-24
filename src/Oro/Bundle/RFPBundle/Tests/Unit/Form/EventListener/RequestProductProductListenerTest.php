<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\EventListener\RequestProductProductListener;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductKitItemLineItemCollectionType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class RequestProductProductListenerTest extends TestCase
{
    private EntityStateChecker|MockObject $entityStateChecker;

    private RequestProductProductListener $listener;

    private FormFactoryInterface $formFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityStateChecker = $this->createMock(EntityStateChecker::class);

        $this->listener = new RequestProductProductListener($this->entityStateChecker);

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addType(new RequestProductKitItemLineItemCollectionType())
            ->getFormFactory();
    }

    public function testOnPostSetDataWhenHasData(): void
    {
        $product = (new Product())->setType(Product::TYPE_KIT);
        $requestProduct = (new RequestProduct())
            ->setProduct($product);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $requestProduct)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', RequestProductKitItemLineItemCollectionType::class);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($product, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSetDataWhenNoData(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', RequestProductKitItemLineItemCollectionType::class);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSubmitWhenNoRequestProductAndNoSubmittedData(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', RequestProductKitItemLineItemCollectionType::class);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $form->submit([]);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSubmitWhenNoRequestProductAndHasSubmittedProductKit(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', RequestProductKitItemLineItemCollectionType::class);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $product = (new Product())->setType(Product::TYPE_KIT);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $form->submit(['product' => $product]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($product, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSubmitWhenNoRequestProductAndHasSubmittedSimpleProduct(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', RequestProductKitItemLineItemCollectionType::class);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $product = (new Product())->setType(Product::TYPE_SIMPLE);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $form->submit(['product' => $product]);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($product, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
    }

    public function testOnPostSubmitWhenHasRequestProductAndNoOriginalProduct(): void
    {
        $productKit = (new Product())->setType(Product::TYPE_KIT);
        $requestProduct = new RequestProduct();

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $requestProduct)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', RequestProductKitItemLineItemCollectionType::class);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $this->entityStateChecker
            ->expects(self::once())
            ->method('getOriginalEntityFieldData')
            ->with($requestProduct, 'product')
            ->willReturn(null);

        $form->submit(['product' => $productKit]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(new ArrayCollection(), $form->get('kitItemLineItems')->getConfig()->getOption('data'));
    }

    public function testOnPostSubmitWhenHasRequestProductAndProductChanged(): void
    {
        $productSimple = (new Product())->setType(Product::TYPE_SIMPLE);
        $productKit = (new Product())->setType(Product::TYPE_KIT);
        $requestProduct = (new RequestProduct())
            ->setProduct($productSimple);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $requestProduct)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', RequestProductKitItemLineItemCollectionType::class);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productSimple, $form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $this->entityStateChecker
            ->expects(self::once())
            ->method('getOriginalEntityFieldData')
            ->with($requestProduct, 'product')
            ->willReturn($productSimple);

        $form->submit(['product' => $productKit]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(new ArrayCollection(), $form->get('kitItemLineItems')->getConfig()->getOption('data'));
    }

    public function testOnPostSubmitWhenHasRequestProductAndProductNotChanged(): void
    {
        $productKit = (new Product())->setType(Product::TYPE_KIT);
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $requestProduct)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', RequestProductKitItemLineItemCollectionType::class);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));

        $this->entityStateChecker
            ->expects(self::once())
            ->method('getOriginalEntityFieldData')
            ->with($requestProduct, 'product')
            ->willReturn($productKit);

        $form->submit(['product' => $productKit]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('data'));
    }
}
