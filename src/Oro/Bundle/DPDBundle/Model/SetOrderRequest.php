<?php

namespace Oro\Bundle\DPDBundle\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

class SetOrderRequest extends ParameterBag
{
    const START_ORDER_ACTION = 'startOrder';
    const CHECK_ORDER_ACTION = 'checkOrderData';

    const FIELD_ORDER_ACTION = 'order_action';
    const FIELD_SHIP_DATE = 'ship_date';
    const FIELD_LABEL_SIZE = 'label_size';
    const FIELD_LABEL_START_POSITION = 'label_start_position';
    const FIELD_ORDER_DATA_LIST = 'order_data_list';

    /**
     * SetOrderRequest constructor.
     */
    public function __construct(array $params)
    {
        parent::__construct($params);
    }

    /**
     * @return string
     */
    public function getOrderAction()
    {
        return $this->get(self::FIELD_ORDER_ACTION);
    }

    /**
     * @return \DateTime
     */
    public function getShipDate()
    {
        return $this->get(self::FIELD_SHIP_DATE);
    }

    /**
     * @return string
     */
    public function getLabelSize()
    {
        return $this->get(self::FIELD_LABEL_SIZE);
    }

    /**
     * @return string
     */
    public function getLabelStartPosition()
    {
        return $this->get(self::FIELD_LABEL_START_POSITION);
    }

    /**
     * @return OrderData[]
     */
    public function getOrderDataList()
    {
        return $this->get(self::FIELD_ORDER_DATA_LIST);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $request = [
            'OrderAction' => $this->getOrderAction(),
            'OrderSettings' => [
                'ShipDate' => $this->getShipDate()->format('Y-m-d'),
                'LabelSize' => $this->getLabelSize(),
                'LabelStartPosition' => $this->getLabelStartPosition(),
            ],
        ];

        foreach ($this->getOrderDataList() as $orderData) {
            $request['OrderDataList'][] = $orderData->toArray();
        }

        return $request;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
