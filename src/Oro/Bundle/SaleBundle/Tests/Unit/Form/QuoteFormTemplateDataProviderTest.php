<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\Form\QuoteFormTemplateDataProvider;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\ClassWithToString;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class QuoteFormTemplateDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var QuoteProductPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var QuoteAddressSecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $addressSecurityProvider;

    /** @var QuoteFormTemplateDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
        $this->productPriceProvider = $this->createMock(QuoteProductPriceProvider::class);
        $this->addressSecurityProvider = $this->createMock(QuoteAddressSecurityProvider::class);

        $this->provider = new QuoteFormTemplateDataProvider(
            $this->dispatcher,
            $this->productPriceProvider,
            $this->addressSecurityProvider
        );
    }

    public function testGetData()
    {
        $quote = new Quote();

        $formName = 'any_form_type';

        $payload = ['payload' => 'data'];

        $form = $this->createForm($formName, $quote);

        $request = $this->createRequest();
        $request->expects($this->once())->method('get')->with($formName)->willReturn($payload);

        $this->dispatcher->addListener(
            QuoteEvent::NAME,
            function (QuoteEvent $event) use ($form, $quote, $payload) {
                $this->assertSame($form, $event->getForm());
                $this->assertSame($quote, $event->getQuote());
                $this->assertEquals($payload, $event->getSubmittedData());
                $data = $event->getData();
                $data->offsetSet('quoteData', 'test');
            }
        );

        $formView = $this->createMock(FormView::class);

        $form->expects($this->once())->method('createView')->willReturn($formView);
        $this->productPriceProvider->expects($this->once())
            ->method('getTierPrices')->with($quote)->willReturn(['$5', '$42', '$100500']);
        $this->productPriceProvider->expects($this->once())->method('getMatchedPrices')->willReturn(['$42']);
        $this->addressSecurityProvider->expects($this->once())
            ->method('isAddressGranted')->with($quote, AddressType::TYPE_SHIPPING)->willReturn(true);

        $result = $this->provider->getData($quote, $form, $request);

        $this->assertEquals(
            [
                'entity' => $quote,
                'form' => $formView,
                'tierPrices' => ['$5', '$42', '$100500'],
                'matchedPrices' => ['$42'],
                'isShippingAddressGranted' => true,
                'quoteData' => ['quoteData' => 'test']
            ],
            $result
        );
    }

    public function testInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Oro\Bundle\SaleBundle\Form\QuoteFormTemplateDataProvider`' .
            ' supports only `Oro\Bundle\SaleBundle\Entity\Quote` instance as form data (entity)'
        );

        $entity = new ClassWithToString('else');

        $this->provider->getData($entity, $this->createForm(), $this->createRequest());
    }

    /**
     * @param string $name
     * @param Quote $quote
     * @return \PHPUnit\Framework\MockObject\MockObject|FormInterface
     */
    private function createForm($name = 'test_type', Quote $quote = null)
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())->method('getName')->willReturn($name);

        if ($quote) {
            $form->expects($this->any())->method('getData')->willReturn($quote);
        }

        return $form;
    }

    /**
     * @return Request|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRequest()
    {
        return $this->createMock(Request::class);
    }
}
