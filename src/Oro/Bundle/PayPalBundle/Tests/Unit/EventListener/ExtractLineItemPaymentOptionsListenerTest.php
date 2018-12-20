<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PayPalBundle\EventListener\ExtractLineItemPaymentOptionsListener;

class ExtractLineItemPaymentOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtractLineItemPaymentOptionsListener */
    private $listener;

    /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rounder;

    public function setUp()
    {
        /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject $numberFormatter */
        $numberFormatter = $this->getMockBuilder(NumberFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(function ($cost, $currency) {
                return sprintf('%s%s', $currency, round($cost, 2));
            });

        $this->rounder = $this->getMockBuilder(RoundingServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rounder->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($number, $precision) {
                return round($number, $precision);
            });

        $this->listener = new ExtractLineItemPaymentOptionsListener($numberFormatter, $this->rounder);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onExtractLineItemPaymentOptionsData()
    {
        return [
            'Whole qty, cost precision 2' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Product Name',
                    'description' => 'Product Description',
                    'cost' => 123.45,
                    'qty' => 2,
                    'currency' => 'USD',
                    'unit' => 'item',
                ],
                'currencyRoundingPrecision' => 2,
                'expected' => [
                    'name' => 'PRSKU Product Name',
                    'description' => 'Product Description',
                    'cost' => 123.45,
                    'qty' => 2,
                    'currency' => 'USD',
                    'unit' => 'item',
                ],
            ],
            'Fractional qty, cost precision 2' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Long Product Name',
                    'description' => 'Product Description',
                    'cost' => 56.23,
                    'qty' => 0.5,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
                'currencyRoundingPrecision' => 2,
                'expected' => [
                    'name' => 'PRSKU Long Product - EUR56.23x0.5 kg',
                    'description' => 'Product Description',
                    'cost' => 28.12,
                    'qty' => 1,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
            ],
            'Whole qty, cost precision 3' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Long Product Name',
                    'description' => 'Product Description',
                    'cost' => 16.666,
                    'qty' => 2,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
                'currencyRoundingPrecision' => 2,
                'expected' => [
                    'name' => 'PRSKU Long Product N - EUR16.67x2 kg',
                    'description' => 'Product Description',
                    'cost' => 33.33,
                    'qty' => 1,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
            ],
            'Fractional qty, cost precision 3' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Long Product Name',
                    'description' => 'Product Description',
                    'cost' => 13.336,
                    'qty' => 0.2,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
                'currencyRoundingPrecision' => 2,
                'expected' => [
                    'name' => 'PRSKU Long Product - EUR13.34x0.2 kg',
                    'description' => 'Product Description',
                    'cost' => 2.67,
                    'qty' => 1,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
            ],
            'Fractional qty, system currency precision 1' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Long Product Name',
                    'description' => 'Product Description',
                    'cost' => 56.3,
                    'qty' => 0.5,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
                'currencyRoundingPrecision' => 1,
                'expected' => [
                    'name' => 'PRSKU Long Product  - EUR56.3x0.5 kg',
                    'description' => 'Product Description',
                    'cost' => 28.2,
                    'qty' => 1,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
            ],
        ];
    }

    /**
     * @param array $lineItemModelData
     * @param int $currencyRoundingPrecision
     * @param array $expected
     * @dataProvider onExtractLineItemPaymentOptionsData
     */
    public function testOnExtractLineItemPaymentOptions(
        array $lineItemModelData,
        $currencyRoundingPrecision,
        array $expected
    ) {
        $this->rounder->expects($this->any())
            ->method('getPrecision')
            ->willReturn($currencyRoundingPrecision);

        $lineItemModel = new LineItemOptionModel();

        $lineItemModel->setName($lineItemModelData['name']);
        $lineItemModel->setDescription($lineItemModelData['description']);
        $lineItemModel->setCost($lineItemModelData['cost']);
        $lineItemModel->setQty($lineItemModelData['qty']);
        $lineItemModel->setCurrency($lineItemModelData['currency']);
        $lineItemModel->setUnit($lineItemModelData['unit']);

        $entity = new Order();
        $event = new ExtractLineItemPaymentOptionsEvent($entity);

        $this->assertEmpty($event->getModels());
        $event->addModel($lineItemModel);

        $this->listener->onExtractLineItemPaymentOptions($event);

        $result = $event->getModels()[0];

        $this->assertEquals($expected['name'], $result->getName());
        $this->assertEquals($expected['description'], $result->getDescription());
        $this->assertEquals($expected['cost'], round($result->getCost(), 2), '', 1e-6);
        $this->assertEquals($expected['qty'], $result->getQty(), '', 1e-6);
        $this->assertEquals($expected['currency'], $result->getCurrency());
        $this->assertEquals($expected['unit'], $result->getUnit());
    }
}
