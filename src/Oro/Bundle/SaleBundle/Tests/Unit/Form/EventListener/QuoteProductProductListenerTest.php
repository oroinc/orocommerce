<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\FormTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteProductProductListener;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductKitItemLineItemCollectionType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class QuoteProductProductListenerTest extends TestCase
{
    private EntityStateChecker|MockObject $entityStateChecker;

    private QuoteProductProductListener $listener;

    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->entityStateChecker = $this->createMock(EntityStateChecker::class);

        $this->listener = new QuoteProductProductListener($this->entityStateChecker);

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addType(new QuoteProductKitItemLineItemCollectionType())
            ->addType(new FormTypeStub(['entry_options']))
            ->getFormFactory();
    }

    public function testOnPostSetDataWhenHasData(): void
    {
        $product = (new ProductStub())->setType(Product::TYPE_KIT);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($product);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $quoteProduct)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', QuoteProductKitItemLineItemCollectionType::class)
            ->add('quoteProductOffers', FormTypeStub::class, [
                'entry_options' => [
                    'allow_prices_override' => true,
                ]
            ]);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($product, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => false],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );
    }

    public function testOnPostSetDataWhenNoData(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', QuoteProductKitItemLineItemCollectionType::class)
            ->add('quoteProductOffers', FormTypeStub::class, [
                'entry_options' => [
                    'allow_prices_override' => true,
                ]
            ]);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => true],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );
    }

    public function testOnPostSubmitWhenNoQuoteProductAndNoSubmittedData(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', QuoteProductKitItemLineItemCollectionType::class)
            ->add('quoteProductOffers', FormTypeStub::class, [
                'entry_options' => [
                    'allow_prices_override' => true,
                ]
            ]);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => true],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );

        $form->submit([]);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => true],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );
    }

    public function testOnPostSubmitWhenNoQuoteProductAndHasSubmittedProductKit(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', QuoteProductKitItemLineItemCollectionType::class)
            ->add('quoteProductOffers', FormTypeStub::class, [
                'entry_options' => [
                    'allow_prices_override' => true,
                ]
            ]);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $product = (new ProductStub())->setType(Product::TYPE_KIT);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => true],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );

        $form->submit(['product' => $product]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($product, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => false],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );
    }

    public function testOnPostSubmitWhenNoQuoteProductAndHasSubmittedSimpleProduct(): void
    {
        $formBuilder = $this->formFactory->createBuilder()
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', QuoteProductKitItemLineItemCollectionType::class)
            ->add('quoteProductOffers', FormTypeStub::class, [
                'entry_options' => [
                    'allow_prices_override' => true,
                ]
            ]);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        $product = (new ProductStub())->setType(Product::TYPE_SIMPLE);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => true],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );

        $form->submit(['product' => $product]);

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($product, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => true],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );
    }

    public function testOnPostSubmitWhenHasQuoteProductAndNoOriginalProduct(): void
    {
        $productKit = (new ProductStub())->setType(Product::TYPE_KIT);
        $quoteProduct = new QuoteProduct();

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $quoteProduct)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', QuoteProductKitItemLineItemCollectionType::class)
            ->add('quoteProductOffers', FormTypeStub::class, [
                'entry_options' => [
                    'allow_prices_override' => true,
                ]
            ]);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => true],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );

        $this->entityStateChecker
            ->expects(self::once())
            ->method('getOriginalEntityFieldData')
            ->with($quoteProduct, 'product')
            ->willReturn(null);

        $form->submit(['product' => $productKit]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(new ArrayCollection(), $form->get('kitItemLineItems')->getConfig()->getOption('data'));
        self::assertEquals(
            ['allow_prices_override' => false],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );
    }

    public function testOnPostSubmitWhenHasQuoteProductAndProductChanged(): void
    {
        $productSimple = (new ProductStub())->setType(Product::TYPE_SIMPLE);
        $productKit = (new ProductStub())->setType(Product::TYPE_KIT);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productSimple);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $quoteProduct)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', QuoteProductKitItemLineItemCollectionType::class)
            ->add('quoteProductOffers', FormTypeStub::class, [
                'entry_options' => [
                    'allow_prices_override' => true,
                ]
            ]);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertFalse($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productSimple, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => true],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );

        $this->entityStateChecker
            ->expects(self::once())
            ->method('getOriginalEntityFieldData')
            ->with($quoteProduct, 'product')
            ->willReturn($productSimple);

        $form->submit(['product' => $productKit]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(new ArrayCollection(), $form->get('kitItemLineItems')->getConfig()->getOption('data'));
        self::assertEquals(
            ['allow_prices_override' => false],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );
    }

    public function testOnPostSubmitWhenHasQuoteProductAndProductNotChanged(): void
    {
        $productKit = (new ProductStub())->setType(Product::TYPE_KIT);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productKit);

        $formBuilder = $this->formFactory->createBuilder(FormType::class, $quoteProduct)
            ->add('product', FormType::class, ['compound' => false])
            ->add('kitItemLineItems', QuoteProductKitItemLineItemCollectionType::class)
            ->add('quoteProductOffers', FormTypeStub::class, [
                'entry_options' => [
                    'allow_prices_override' => true,
                ]
            ]);

        $formBuilder
            ->get('product')
            ->addEventSubscriber($this->listener);

        $form = $formBuilder->getForm();

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertEquals(
            ['allow_prices_override' => false],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );

        $this->entityStateChecker
            ->expects(self::once())
            ->method('getOriginalEntityFieldData')
            ->with($quoteProduct, 'product')
            ->willReturn($productKit);

        $form->submit(['product' => $productKit]);

        self::assertTrue($form->get('kitItemLineItems')->getConfig()->getOption('required'));
        self::assertSame($productKit, $form->get('kitItemLineItems')->getConfig()->getOption('product'));
        self::assertNull($form->get('kitItemLineItems')->getConfig()->getOption('data'));
        self::assertEquals(
            ['allow_prices_override' => false],
            $form->get('quoteProductOffers')->getConfig()->getOption('entry_options')
        );
    }
}
