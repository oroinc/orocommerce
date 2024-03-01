<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\EventListener\QuoteProductKitLineItemListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

class QuoteProductKitLineItemListenerTest extends TestCase
{
    private Environment|MockObject $twig;

    private QuoteProductKitLineItemListener $listener;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $this->listener = new QuoteProductKitLineItemListener($this->twig);
    }

    public function testOnQuoteEventWhenNoQuoteProducts(): void
    {
        $form = $this->createMock(FormInterface::class);
        $quoteProductsForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->with('quoteProducts')
            ->willReturn($quoteProductsForm);
        $quoteProductsForm->expects(self::once())
            ->method('all')
            ->willReturn([]);
        $quote = new Quote();
        $event = new QuoteEvent($form, $quote);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(new \ArrayObject(['kitItemLineItems' => [], 'disabledKitPrices' => []]), $event->getData());
    }

    public function testOnQuoteEventWhenHasQuoteProductWithoutData(): void
    {
        $form = $this->createMock(FormInterface::class);
        $quoteProductForm = $this->createMock(FormInterface::class);
        $quoteProductsForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->with('quoteProducts')
            ->willReturn($quoteProductsForm);
        $quoteProductsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([$quoteProductForm]);
        $quote = new Quote();
        $event = new QuoteEvent($form, $quote);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(new \ArrayObject(['kitItemLineItems' => [], 'disabledKitPrices' => []]), $event->getData());
    }

    public function testOnQuoteEventWhenHasQuoteProductWithoutProduct(): void
    {
        $form = $this->createMock(FormInterface::class);
        $quoteProductForm = $this->createMock(FormInterface::class);
        $quoteProductsForm = $this->createMock(FormInterface::class);
        $quoteProduct = new QuoteProduct();
        $quoteProductForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quoteProduct);
        $form
            ->expects(self::once())
            ->method('get')
            ->with('quoteProducts')
            ->willReturn($quoteProductsForm);
        $quoteProductsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([$quoteProductForm]);
        $quote = new Quote();
        $event = new QuoteEvent($form, $quote);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(new \ArrayObject(['kitItemLineItems' => [], 'disabledKitPrices' => []]), $event->getData());
    }

    public function testOnQuoteEventWhenHasQuoteProductWithNotProductKit(): void
    {
        $form = $this->createMock(FormInterface::class);
        $quoteProductForm = $this->createMock(FormInterface::class);
        $quoteProductsForm = $this->createMock(FormInterface::class);
        $quoteProduct = (new QuoteProduct())
            ->setProduct(new ProductStub());
        $quoteProductForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quoteProduct);
        $form
            ->expects(self::once())
            ->method('get')
            ->with('quoteProducts')
            ->willReturn($quoteProductsForm);
        $quoteProductsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([$quoteProductForm]);
        $quote = new Quote();
        $event = new QuoteEvent($form, $quote);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(new \ArrayObject(['kitItemLineItems' => [], 'disabledKitPrices' => []]), $event->getData());
    }

    public function testOnQuoteEventWhenHasQuoteProductWithProductKit(): void
    {
        $form = $this->createMock(FormInterface::class);
        $quoteProductForm = $this->createMock(FormInterface::class);
        $quoteProductsForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->with('quoteProducts')
            ->willReturn($quoteProductsForm);
        $quoteProductsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([$quoteProductForm]);
        $quote = new Quote();
        $event = new QuoteEvent($form, $quote);
        $quoteProduct = (new QuoteProduct())
            ->setProduct((new ProductStub())->setType(Product::TYPE_KIT));
        $quoteProductForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quoteProduct);
        $formView = new FormView();
        $formView->vars['full_name'] = 'form_full_name';
        $formView->children['kitItemLineItems'] = new FormView();
        $quoteProductForm
            ->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $html = 'rendered template';
        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('@OroSale/Form/kitItemLineItems.html.twig', ['form' => $formView['kitItemLineItems']])
            ->willReturn($html);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(
            new \ArrayObject(
                [
                    'kitItemLineItems' => [$formView->vars['full_name'] => $html],
                    'disabledKitPrices' => [$formView->vars['full_name'] => true],
                ]
            ),
            $event->getData()
        );
    }
}
