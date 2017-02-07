<?php

namespace Oro\Bundle\DPDBundle\Factory;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\OrderData;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

class DPDRequestFactory
{
    /**
     * @param DPDTransport    $transport
     * @param ShippingService $shippingService
     * @param string          $requestAction
     * @param \DateTime       $shipDate
     * @param OrderAddress    $orderAddress
     * @param $orderEmail
     * @param array $packages
     *
     * @return SetOrderRequest
     */
    public function createSetOrderRequest(
        DPDTransport $transport,
        ShippingService $shippingService,
        $requestAction,
        \DateTime $shipDate,
        $orderId,
        OrderAddress $orderAddress,
        $orderEmail,
        array $packages
    ) {
        $orderDataList = [];
        /** @var Package $package */
        foreach ($packages as $idx => $package) {
            $orderDataList[] = new OrderData(
                [
                    OrderData::FIELD_SHIP_TO_ADDRESS => $orderAddress,
                    OrderData::FIELD_SHIP_TO_EMAIL => $orderEmail,
                    OrderData::FIELD_PARCEL_SHOP_ID => 0,
                    OrderData::FIELD_SHIP_SERVICE_CODE => $shippingService->getCode(),
                    OrderData::FIELD_WEIGHT => $package->getWeight(),
                    OrderData::FIELD_CONTENT => $package->getContents(),
                    OrderData::FIELD_YOUR_INTERNAL_ID => $idx,
                    OrderData::FIELD_REFERENCE1 => $package->getContents(),
                    OrderData::FIELD_REFERENCE2 => $orderId,
                ]
            );
        }

        $setOrderRequest = new SetOrderRequest(
            [
                SetOrderRequest::FIELD_ORDER_ACTION => $requestAction,
                SetOrderRequest::FIELD_SHIP_DATE => $shipDate,
                SetOrderRequest::FIELD_LABEL_SIZE => $transport->getLabelSize(),
                SetOrderRequest::FIELD_LABEL_START_POSITION => $transport->getLabelStartPosition(),
                SetOrderRequest::FIELD_ORDER_DATA_LIST => $orderDataList,
            ]
        );

        return $setOrderRequest;
    }

    /**
     * @return ZipCodeRulesRequest
     */
    public function createZipCodeRulesRequest()
    {
        return new ZipCodeRulesRequest();
    }
}
