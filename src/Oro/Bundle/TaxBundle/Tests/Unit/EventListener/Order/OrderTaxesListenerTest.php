<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\TaxBundle\EventListener\Order\OrderTaxesListener;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderTaxesListenerTest extends TestCase
{
    private TaxProviderInterface|MockObject $taxProvider;

    private OrderEvent|MockObject $event;

    private TaxationSettingsProvider|MockObject $taxationSettingsProvider;

    private OrderTaxesListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->event = $this->createMock(OrderEvent::class);

        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->listener = new OrderTaxesListener($taxProviderRegistry, $this->taxationSettingsProvider);
    }

    /**
     * @dataProvider onOrderEventDataProvider
     */
    public function testOnOrderEvent(Result $result, array $expectedResult): void
    {
        $order = new Order();
        $data = new \ArrayObject();

        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->with($order)
            ->willReturn($result);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->event->expects(self::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->event->expects(self::once())
            ->method('getData')
            ->willReturn($data);

        $this->listener->onOrderEvent($this->event);

        self::assertEquals($data->getArrayCopy(), $expectedResult);
    }

    public function testOnOrderEventTaxationDisabled(): void
    {
        $this->taxProvider->expects(self::never())
            ->method(self::anything());
        $this->event->expects(self::never())
            ->method(self::anything());

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->listener->onOrderEvent($this->event);
    }

    public function onOrderEventDataProvider(): array
    {
        $taxResult = TaxResultElement::create('TAX', 0.1, 50, 5);
        $taxResult->offsetSet(AbstractResultElement::CURRENCY, 'USD');

        $lineItem = new Result();
        $lineItem->offsetSet(Result::UNIT, ResultElement::create(11, 10, 1, 0));
        $lineItem->offsetSet(Result::ROW, ResultElement::create(55, 50, 5, 0));
        $lineItem->offsetSet(Result::TAXES, [$taxResult]);

        $result = new Result();
        $result->offsetSet(Result::TOTAL, ResultElement::create(55, 50, 5, 0));
        $result->offsetSet(Result::ITEMS, [$lineItem]);
        $result->offsetSet(Result::TAXES, [$taxResult]);

        return [
            [
                $result,
                [
                    'taxItems' => [
                        [
                            'unit' => [
                                'includingTax' => 11,
                                'excludingTax' => 10,
                                'taxAmount' => 1,
                                'adjustment' => 0,
                            ],
                            'row' => [
                                'includingTax' => 55,
                                'excludingTax' => 50,
                                'taxAmount' => 5,
                                'adjustment' => 0,
                            ],
                            'taxes' => [
                                [
                                    'tax' => 'TAX',
                                    'rate' => '0.1',
                                    'taxableAmount' => '50',
                                    'taxAmount' => '5',
                                    'currency' => 'USD',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testOnOrderEventNoData(): void
    {
        $order = new Order();
        $data = new \ArrayObject();

        $this->taxProvider->expects(self::once())
            ->method('getTax')
            ->with($order)
            ->willReturn(new Result());

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->event->expects(self::once())
            ->method('getOrder')
            ->willReturn($order);

        $this->event->expects(self::once())
            ->method('getData')
            ->willReturn($data);

        $this->listener->onOrderEvent($this->event);
    }
}
