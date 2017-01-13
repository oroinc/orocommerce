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
     * @param DPDTransport $transport
     * @param ShippingService $shippingService
     * @param string $requestAction
     * @param \DateTime $shipDate
     * @param OrderAddress $orderAddress
     * @param $orderEmail
     * @param array $packages
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
            $orderDataList[] = (new OrderData())
                ->setShipToAddress($orderAddress)
                ->setShipToEmail($orderEmail)
                ->setParcelShopId(0)
                ->setShipServiceCode($shippingService->getCode())
                ->setWeight($package->getWeight())
                ->setContent($package->getContents())
                ->setYourInternalId($idx)
                ->setReference1($package->getContents())
                ->setReference2($orderId);
        }

        $setOrderRequest = (new SetOrderRequest())
            ->setOrderAction($requestAction)
            ->setShipDate($shipDate)
            ->setLabelSize($transport->getLabelSize())
            ->setLabelStartPosition($transport->getLabelStartPosition())
            ->setOrderDataList($orderDataList);

        return $setOrderRequest;
    }

    public function createZipCodeRulesRequest() {
        return (new ZipCodeRulesRequest());
    }
}
