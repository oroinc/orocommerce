<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\TaxBundle\EventListener\ExtractLineItemPaymentOptionsListener;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class ExtractLineItemPaymentOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    const TAX_AMOUNT = 1.23;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TaxProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $taxProvider;

    /** @var TaxProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $taxProviderRegistry;

    /** @var ExtractLineItemPaymentOptionsListener */
    private $listener;

    public function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $this->taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);

        $this->listener = new ExtractLineItemPaymentOptionsListener($this->translator, $this->taxProviderRegistry);

        $this->translator->expects($this->any())
            ->method('trans')
            ->with('oro.tax.result.tax')
            ->willReturn('Tax translated');
    }

    public function testOnExtractLineItemPaymentOptions()
    {
        $this->taxProviderRegistry->expects($this->once())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $result = new Result([Result::TOTAL => new ResultElement([ResultElement::TAX_AMOUNT => self::TAX_AMOUNT])]);
        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($result);

        $context = [
            ExtractOptionsProvider::CONTEXT_PAYMENT_METHOD_TYPE =>
                PayPalExpressCheckoutPaymentMethod::CONTEXT_PAYMENT_METHOD_TYPE,
        ];

        $entity = new LineItemsAwareEntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $event->setContext($context);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $models = $event->getModels();

        $this->assertCount(1, $models);
        $this->assertInternalType('array', $models);
        $this->assertContainsOnlyInstancesOf(LineItemOptionModel::class, $models);

        $model = reset($models);

        $this->assertEquals('Tax translated', $model->getName());
        $this->assertEquals('', $model->getDescription());
        $this->assertEquals(self::TAX_AMOUNT, $model->getCost());
        $this->assertEquals(1, $model->getQty());
    }

    public function testOnExtractLineItemPaymentOptionsWithZeroTax()
    {
        $this->taxProviderRegistry->expects($this->once())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $result = new Result([Result::TOTAL => new ResultElement([ResultElement::TAX_AMOUNT => 0])]);
        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($result);

        $context = [
            ExtractOptionsProvider::CONTEXT_PAYMENT_METHOD_TYPE =>
                PayPalExpressCheckoutPaymentMethod::CONTEXT_PAYMENT_METHOD_TYPE,
        ];
        $entity = new LineItemsAwareEntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $event->setContext($context);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $this->assertEmpty($event->getModels());
    }

    public function testOnExtractLineItemPaymentOptionsWithDisabledTaxation()
    {
        $this->taxProviderRegistry->expects($this->once())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new TaxationDisabledException());

        $context = [
            ExtractOptionsProvider::CONTEXT_PAYMENT_METHOD_TYPE =>
                PayPalExpressCheckoutPaymentMethod::CONTEXT_PAYMENT_METHOD_TYPE,
        ];
        $entity = new LineItemsAwareEntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $event->setContext($context);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $this->assertEmpty($event->getModels());
    }

    public function testOnExtractLineItemPaymentOptionsTaxesCouldNotBeLoaded()
    {
        $this->taxProviderRegistry->expects($this->once())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new \InvalidArgumentException());

        $context = [
            ExtractOptionsProvider::CONTEXT_PAYMENT_METHOD_TYPE =>
                PayPalExpressCheckoutPaymentMethod::CONTEXT_PAYMENT_METHOD_TYPE,
        ];

        $entity = new LineItemsAwareEntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $event->setContext($context);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $this->assertEmpty($event->getModels());
    }


    /**
     * @dataProvider onExtractLineItemPaymentOptionsWithNotApplicableContextProvider
     * @param array $context
     */
    public function testOnExtractLineItemPaymentOptionsWithNotApplicableContext(array $context)
    {
        $this->taxProviderRegistry->expects($this->never())
            ->method('getEnabledProvider');

        $this->taxProvider->expects($this->never())
            ->method('loadTax');

        $entity = new LineItemsAwareEntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $event->setContext($context);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $this->assertEmpty($event->getModels());
    }

    /**
     * @return array
     */
    public function onExtractLineItemPaymentOptionsWithNotApplicableContextProvider()
    {
        return [
            'with not applicable payment method' => [
                'context' => [ExtractOptionsProvider::CONTEXT_PAYMENT_METHOD_TYPE => 'not_applicable_method'],
            ],
            'without specified payment method' => [
                'context' => [],
            ],
        ];
    }
}
