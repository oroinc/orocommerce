<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\OptionsProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Provider\PaymentOrderLineItemOptionsProvider;
use Oro\Bundle\PayPalBundle\OptionsProvider\LineItemOptionsFormatter;
use Oro\Bundle\PayPalBundle\OptionsProvider\LineItemOptionsProvider;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\TaxAmountProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class LineItemOptionsProviderTest extends TestCase
{
    private PaymentOrderLineItemOptionsProvider|MockObject $orderLineItemOptionsProvider;

    private TaxAmountProvider|MockObject $taxAmountProvider;

    private TranslatorInterface|MockObject $translator;

    private LineItemOptionsFormatter|MockObject $lineItemOptionsFormatter;

    private LineItemOptionsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderLineItemOptionsProvider = $this->createMock(PaymentOrderLineItemOptionsProvider::class);
        $this->taxAmountProvider = $this->createMock(TaxAmountProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->lineItemOptionsFormatter = $this->createMock(LineItemOptionsFormatter::class);

        $this->provider = new LineItemOptionsProvider(
            $this->orderLineItemOptionsProvider,
            $this->taxAmountProvider,
            $this->translator,
            $this->lineItemOptionsFormatter
        );
    }

    public function testGetLineItemOptionsWithoutProductPriceIncludeTax(): void
    {
        $lineItemModel = new LineItemOptionModel();
        $lineItemModel->setName('PRSKU Product Name');
        $lineItemModel->setDescription('Product Description');
        $lineItemModel->setCost(123.45);
        $lineItemModel->setQty(2);
        $lineItemModel->setCurrency('USD');
        $lineItemModel->setUnit('item');

        $taxName = 'Tax';
        $taxAmount = 3.6;
        $taxLineItemModel = new LineItemOptionModel();
        $taxLineItemModel->setName($taxName);
        $taxLineItemModel->setDescription('');
        $taxLineItemModel->setCost($taxAmount);
        $taxLineItemModel->setQty(1);

        $order = new Order();
        $this->orderLineItemOptionsProvider
            ->expects($this->once())
            ->method('getLineItemOptions')
            ->with($order)
            ->willReturn([$lineItemModel]);

        $this->taxAmountProvider
            ->expects($this->once())
            ->method('isTotalIncludedTax')
            ->willReturn(false);

        $this->taxAmountProvider
            ->expects($this->once())
            ->method('getExcludedTaxAmount')
            ->with($order)
            ->willReturn($taxAmount);

        $this->translator
            ->expects($this->atLeastOnce())
            ->method('trans')
            ->with('oro.tax.result.tax')
            ->willReturn($taxName);

        $this->lineItemOptionsFormatter
            ->expects($this->once())
            ->method('formatLineItemOptions')
            ->with([$lineItemModel, $taxLineItemModel])
            ->willReturnArgument(0);

        $actual = $this->provider->getLineItemOptions($order);

        $expected = [
            $lineItemModel,
            $taxLineItemModel,
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testGetLineItemOptionsWithoutProductPriceIncludeTaxAndDisabledTaxation(): void
    {
        $lineItemModel = new LineItemOptionModel();
        $lineItemModel->setName('PRSKU Product Name');
        $lineItemModel->setDescription('Product Description');
        $lineItemModel->setCost(123.45);
        $lineItemModel->setQty(2);
        $lineItemModel->setCurrency('USD');
        $lineItemModel->setUnit('item');

        $order = new Order();
        $this->orderLineItemOptionsProvider
            ->expects($this->once())
            ->method('getLineItemOptions')
            ->with($order)
            ->willReturn([$lineItemModel]);

        $this->taxAmountProvider
            ->expects($this->once())
            ->method('isTotalIncludedTax')
            ->willReturn(false);

        $this->taxAmountProvider
            ->expects($this->once())
            ->method('getExcludedTaxAmount')
            ->with($order)
            ->willThrowException(new TaxationDisabledException());

        $this->lineItemOptionsFormatter
            ->expects($this->once())
            ->method('formatLineItemOptions')
            ->with([$lineItemModel])
            ->willReturnArgument(0);

        $actual = $this->provider->getLineItemOptions($order);

        $expected = [$lineItemModel];

        $this->assertEquals($expected, $actual);
    }

    public function testGetLineItemOptionsWithProductPriceIncludeTax(): void
    {
        $lineItemModel = new LineItemOptionModel();
        $lineItemModel->setName('PRSKU Product Name');
        $lineItemModel->setDescription('Product Description');
        $lineItemModel->setCost(123.45);
        $lineItemModel->setQty(2);
        $lineItemModel->setCurrency('USD');
        $lineItemModel->setUnit('item');

        $order = new Order();
        $this->orderLineItemOptionsProvider
            ->expects($this->once())
            ->method('getLineItemOptions')
            ->with($order)
            ->willReturn([$lineItemModel]);

        $this->taxAmountProvider
            ->expects($this->once())
            ->method('isTotalIncludedTax')
            ->willReturn(true);

        $this->taxAmountProvider
            ->expects($this->never())
            ->method('getExcludedTaxAmount');

        $this->lineItemOptionsFormatter
            ->expects($this->once())
            ->method('formatLineItemOptions')
            ->with([$lineItemModel])
            ->willReturnArgument(0);

        $actual = $this->provider->getLineItemOptions($order);

        $expected = [$lineItemModel];

        $this->assertEquals($expected, $actual);
    }
}
