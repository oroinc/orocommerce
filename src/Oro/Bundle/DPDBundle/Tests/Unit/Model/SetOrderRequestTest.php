<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Model;

use Oro\Bundle\DPDBundle\Model\OrderData;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class SetOrderRequestTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testConstructionAndGetters()
    {
        $params = [
            SetOrderRequest::FIELD_ORDER_ACTION => SetOrderRequest::CHECK_ORDER_ACTION,
            SetOrderRequest::FIELD_SHIP_DATE => new \DateTime(),
            SetOrderRequest::FIELD_LABEL_SIZE => 'label size',
            SetOrderRequest::FIELD_LABEL_START_POSITION => 'label start position',
            SetOrderRequest::FIELD_ORDER_DATA_LIST => [new OrderData([])],
        ];

        $setOrderRequest = new SetOrderRequest($params);

        $getterValues = [
            SetOrderRequest::FIELD_ORDER_ACTION => $setOrderRequest->getOrderAction(),
            SetOrderRequest::FIELD_SHIP_DATE => $setOrderRequest->getShipDate(),
            SetOrderRequest::FIELD_LABEL_SIZE => $setOrderRequest->getLabelSize(),
            SetOrderRequest::FIELD_LABEL_START_POSITION => $setOrderRequest->getLabelStartPosition(),
            SetOrderRequest::FIELD_ORDER_DATA_LIST => $setOrderRequest->getOrderDataList(),
        ];

        $this->assertEquals($params, $getterValues);
    }

    public function testToArray()
    {
        $shipDate = new \DateTime();

        $orderData = $this->createMock(OrderData::class);
        $orderData->expects(static::once())
            ->method('toArray')
            ->willReturn(['OrderData']);

        $params = [
            SetOrderRequest::FIELD_ORDER_ACTION => SetOrderRequest::CHECK_ORDER_ACTION,
            SetOrderRequest::FIELD_SHIP_DATE => $shipDate,
            SetOrderRequest::FIELD_LABEL_SIZE => 'label size',
            SetOrderRequest::FIELD_LABEL_START_POSITION => 'label start position',
            SetOrderRequest::FIELD_ORDER_DATA_LIST => [$orderData],
        ];

        $request = new SetOrderRequest($params);
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
