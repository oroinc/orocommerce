<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\Form\QuoteFormTemplateDataProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\ClassWithToString;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class QuoteFormTemplateDataProviderTest extends TestCase
{
    private EventDispatcherInterface $dispatcher;

    private QuoteFormTemplateDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();

        $this->provider = new QuoteFormTemplateDataProvider($this->dispatcher);
    }

    public function testGetData(): void
    {
        $quote = new Quote();

        $formName = 'any_form_type';

        $payload = ['payload' => 'data'];

        $form = $this->createForm($formName, $quote);

        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('get')->with($formName)->willReturn($payload);

        $this->dispatcher->addListener(
            QuoteEvent::NAME,
            function (QuoteEvent $event) use ($form, $quote, $payload) {
                self::assertSame($form, $event->getForm());
                self::assertSame($quote, $event->getQuote());
                self::assertEquals($payload, $event->getSubmittedData());

                $event->getData()->offsetSet('quoteData', 'test');
            }
        );

        $formView = $this->createMock(FormView::class);

        $form->expects(self::once())->method('createView')->willReturn($formView);

        $result = $this->provider->getData($quote, $form, $request);

        self::assertEquals(
            [
                'entity' => $quote,
                'form' => $formView,
                'quoteData' => ['quoteData' => 'test'],
            ],
            $result
        );
    }

    public function testInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Oro\Bundle\SaleBundle\Form\QuoteFormTemplateDataProvider`' .
            ' supports only `Oro\Bundle\SaleBundle\Entity\Quote` instance as form data (entity)'
        );

        $entity = new ClassWithToString('else');

        $this->provider->getData($entity, $this->createForm(), $this->createMock(Request::class));
    }

    private function createForm(string $name = 'test_type', ?Quote $quote = null): FormInterface|MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->method('getName')
            ->willReturn($name);

        if ($quote) {
            $form
                ->method('getData')
                ->willReturn($quote);
        }

        return $form;
    }
}
