<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\EventListener\QuoteProductOfferChecksumQuoteEventListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class QuoteProductOfferChecksumQuoteEventListenerTest extends TestCase
{
    private QuoteProductOfferChecksumQuoteEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new QuoteProductOfferChecksumQuoteEventListener();
    }

    public function testOnQuoteEventWhenNoQuoteProducts(): void
    {
        $form = $this->createMock(FormInterface::class);
        $quoteProductsForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->willReturn($quoteProductsForm);
        $quoteProductsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([]);
        $quote = new Quote();
        $event = new QuoteEvent($form, $quote);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(new \ArrayObject(['checksum' => []]), $event->getData());
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
        $order = new Quote();
        $event = new QuoteEvent($form, $order);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(new \ArrayObject(['checksum' => []]), $event->getData());
    }

    public function testOnQuoteEventWhenHasQuoteProductWithoutProduct(): void
    {
        $form = $this->createMock(FormInterface::class);
        $quoteProductForm = $this->createMock(FormInterface::class);
        $quoteProductsForm = $this->createMock(FormInterface::class);
        $quoteProductOffersForm = $this->createMock(FormInterface::class);
        $quoteProduct = new QuoteProduct();

        $quoteProductForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quoteProduct);

        $form
            ->expects(self::once())
            ->method('get')
            ->willReturn($quoteProductsForm);

        $quoteProductsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([$quoteProductForm]);

        $quoteProductForm
            ->expects(self::once())
            ->method('get')
            ->with('quoteProductOffers')
            ->willReturn($quoteProductOffersForm);

        $quoteProductOffersForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([]);

        $quote = new Quote();
        $event = new QuoteEvent($form, $quote);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(new \ArrayObject(['checksum' => []]), $event->getData());
    }

    public function testOnQuoteEventWhenHasQuoteProductWithoutOffers(): void
    {
        $form = $this->createMock(FormInterface::class);
        $quoteProductForm = $this->createMock(FormInterface::class);
        $quoteProductsForm = $this->createMock(FormInterface::class);
        $quoteProductOffersForm = $this->createMock(FormInterface::class);
        $quoteProduct = (new QuoteProduct())
            ->setProduct(new ProductStub());

        $form
            ->expects(self::once())
            ->method('get')
            ->with('quoteProducts')
            ->willReturn($quoteProductsForm);

        $quoteProductsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([$quoteProductForm]);

        $quoteProductForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quoteProduct);

        $quoteProductForm
            ->expects(self::once())
            ->method('get')
            ->with('quoteProductOffers')
            ->willReturn($quoteProductOffersForm);

        $quoteProductOffersForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([]);

        $quote = new Quote();
        $event = new QuoteEvent($form, $quote);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(new \ArrayObject(['checksum' => []]), $event->getData());
    }

    public function testOnQuoteEventWhenHasQuoteProductAndOffers(): void
    {
        $form = $this->createMock(FormInterface::class);
        $quoteProductForm = $this->createMock(FormInterface::class);
        $quoteProductsForm = $this->createMock(FormInterface::class);
        $quoteProductOffersForm = $this->createMock(FormInterface::class);
        $quoteProductOfferForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('get')
            ->willReturn($quoteProductsForm);

        $quoteProductsForm
            ->expects(self::once())
            ->method('all')
            ->willReturn([$quoteProductForm]);

        $order = new Quote();
        $event = new QuoteEvent($form, $order);

        $quoteProductOffer = (new QuoteProductOffer())
            ->setChecksum('sample-checksum');
        $quoteProduct = (new QuoteProduct())
            ->setProduct((new ProductStub())->setType(Product::TYPE_KIT))
            ->addQuoteProductOffer($quoteProductOffer);

        $quoteProductForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quoteProduct);

        $quoteProductForm
            ->expects(self::once())
            ->method('get')
            ->with('quoteProductOffers')
            ->willReturn($quoteProductOffersForm);

        $quoteProductOffersForm
            ->expects(self::atLeastOnce())
            ->method('all')
            ->willReturn([$quoteProductOfferForm]);

        $quoteProductOfferForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($quoteProductOffer);

        $formView = new FormView();
        $formView->vars['full_name'] = 'form_full_name';
        $quoteProductOfferForm
            ->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        self::assertEquals(new \ArrayObject(), $event->getData());

        $this->listener->onQuoteEvent($event);

        self::assertEquals(
            new \ArrayObject(
                [
                    'checksum' => [$formView->vars['full_name'] => $quoteProductOffer->getChecksum()],
                ]
            ),
            $event->getData()
        );
    }
}
