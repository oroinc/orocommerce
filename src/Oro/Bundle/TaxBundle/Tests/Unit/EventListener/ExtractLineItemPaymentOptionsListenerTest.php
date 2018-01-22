<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\TaxBundle\EventListener\ExtractLineItemPaymentOptionsListener;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class ExtractLineItemPaymentOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    const TAX_AMOUNT = 1.23;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    /** @var TaxProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $taxProvider;

    /** @var ExtractLineItemPaymentOptionsListener */
    private $listener;

    public function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->once())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->listener = new ExtractLineItemPaymentOptionsListener($this->translator, $taxProviderRegistry);

        $this->translator->expects($this->any())
            ->method('trans')
            ->with('oro.tax.result.tax')
            ->willReturn('Tax translated');
    }

    public function testOnExtractLineItemPaymentOptions()
    {
        $result = new Result([Result::TOTAL => new ResultElement([ResultElement::TAX_AMOUNT => self::TAX_AMOUNT])]);
        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($result);

        $entity = new LineItemsAwareEntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
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
        $result = new Result([Result::TOTAL => new ResultElement([ResultElement::TAX_AMOUNT => 0])]);
        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willReturn($result);

        $entity = new LineItemsAwareEntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $this->assertEmpty($event->getModels());
    }

    public function testOnExtractLineItemPaymentOptionsWithDisabledTaxation()
    {
        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new TaxationDisabledException());

        $entity = new LineItemsAwareEntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $this->assertEmpty($event->getModels());
    }

    public function testOnExtractLineItemPaymentOptionsTaxesCouldNotBeLoaded()
    {
        $this->taxProvider->expects($this->once())
            ->method('loadTax')
            ->willThrowException(new \InvalidArgumentException());

        $entity = new LineItemsAwareEntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $this->listener->onExtractLineItemPaymentOptions($event);

        $this->assertEmpty($event->getModels());
    }
}
