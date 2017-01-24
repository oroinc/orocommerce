<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Model;

use Oro\Bundle\DPDBundle\Model\OrderData;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class SetOrderRequestTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            new SetOrderRequest(),
            [
                ['orderAction', SetOrderRequest::CHECK_ORDER_ACTION],
                ['shipDate', new \DateTime()],
                ['labelSize', 'label size'],
                ['labelStartPosition', 'label start position'],
                ['orderDataList', [new OrderData()], false],
            ]
        );
    }

    public function testToArray()
    {
        $shipDate = new \DateTime();

        $orderData = $this->createMock(OrderData::class);
        $orderData->expects(static::once())
            ->method('toArray')
            ->willReturn(['OrderData']);

        $request = (new SetOrderRequest())
            ->setOrderAction(SetOrderRequest::CHECK_ORDER_ACTION)
            ->setShipDate($shipDate)
            ->setLabelSize('label size')
            ->setLabelStartPosition('label start position')
            ->setOrderDataList([$orderData]);
        self::assertEquals(
            [
                'OrderAction' => SetOrderRequest::CHECK_ORDER_ACTION,
                'OrderSettings' => [
                    'ShipDate' => $shipDate->format('Y-m-d'),
                    'LabelSize' => 'label size',
                    'LabelStartPosition' => 'label start position',
                ],
                'OrderDataList' => [
                    ['OrderData'],
                ],
            ],
            $request->toArray()
        );
    }
}
