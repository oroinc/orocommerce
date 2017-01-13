<?php

namespace Oro\Bundle\DPDBundle\Model;


class SetOrderRequest extends DPDRequest
{
    const START_ORDER_ACTION = 'startOrder';
    const CHECK_ORDER_ACTION = 'checkOrderData';

    /** @var  string  */
    protected $orderAction;

    /** @var  \DateTime */
    protected $shipDate;

    /** @var  string */
    protected $labelSize;

    /** @var  string */
    protected $labelStartPosition;

    /** @var  OrderData[] */
    protected $orderDataList;


    public function __construct()
    {
        $this->orderDataList = [];
    }

    /**
     * @return string
     */
    public function getOrderAction()
    {
        return $this->orderAction;
    }

    /**
     * @param string $orderAction
     * @return SetOrderRequest
     */
    public function setOrderAction($orderAction)
    {
        $this->orderAction = $orderAction;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getShipDate()
    {
        return $this->shipDate;
    }

    /**
     * @param \DateTime $shipDate
     * @return SetOrderRequest
     */
    public function setShipDate($shipDate)
    {
        $this->shipDate = $shipDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelSize()
    {
        return $this->labelSize;
    }

    /**
     * @param string $labelSize
     * @return SetOrderRequest
     */
    public function setLabelSize($labelSize)
    {
        $this->labelSize = $labelSize;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelStartPosition()
    {
        return $this->labelStartPosition;
    }

    /**
     * @param string $labelStartPosition
     * @return SetOrderRequest
     */
    public function setLabelStartPosition($labelStartPosition)
    {
        $this->labelStartPosition = $labelStartPosition;
        return $this;
    }


    /**
     * @return OrderData[]
     */
    public function getOrderDataList()
    {
        return $this->orderDataList;
    }

    /**
     * @param OrderData[] $orderDataList
     * @return SetOrderRequest
     */
    public function setOrderDataList($orderDataList)
    {
        $this->orderDataList = $orderDataList;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $request = [
            'OrderAction' => $this->orderAction,
            'OrderSettings' => [
                'ShipDate' => $this->shipDate->format('Y-m-d'),
                'LabelSize' => $this->labelSize,
                'LabelStartPosition' => $this->labelStartPosition
            ],
        ];

        foreach ($this->orderDataList as $orderData) {
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