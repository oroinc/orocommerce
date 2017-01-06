<?php

namespace Oro\Bundle\DPDBundle\Factory;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Model\GetZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\OrderData;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;

class DPDRequestFactory
{
    /** @var  PackageProvider */
    protected $packageProvider;

    /** @var  OrderShippingContextFactory */
    protected $shippingContextFactory;

    /**
     * DPDRequestFactory constructor.
     * @param OrderShippingContextFactory $shippingContextFactory
     * @param PackageProvider $packageProvider
     */
    public function __construct(
        OrderShippingContextFactory $shippingContextFactory,
        PackageProvider $packageProvider
    ) {
        $this->packageProvider = $packageProvider;
        $this->shippingContextFactory = $shippingContextFactory;
    }

    /**
     * @param DPDTransport $transport
     * @param Order $order
     * @param string $requestAction
     * @param ShippingService $shippingService
     * @param \DateTime $shipDate
     * @return null|SetOrderRequest
     */
    public function createSetOrderRequest(
        DPDTransport $transport,
        Order $order,
        $requestAction,
        ShippingService $shippingService,
        \DateTime $shipDate
    ) {
        //FIXME: could we just use shipping context to get all required info: address, email, phone
        $packages = $this->packageProvider->createPackages($this->shippingContextFactory->create($order));
        if (!$packages) {
            return null;
        }

        if (count($packages) !== 1) { //TODO: implement multi package support
            return null;
        }

        $orderData = (new OrderData())
            ->setShipToAddress($order->getShippingAddress())
            ->setShipToEmail($order->getEmail())
            ->setParcelShopId(0)
            ->setShipServiceCode($shippingService->getCode())
            ->setWeight($packages[0]->getWeight())
            ->setContent('order content') //FIXME: should we build some content description?? how?
            ->setYourInternalId($order->getIdentifier())
            ->setReference1('reference1') //FIXME: ??
            ->setReference2('reference2'); //FIXME: ??

        $setOrderRequest = (new SetOrderRequest())
            ->setOrderAction($requestAction)
            ->setShipDate($shipDate)
            ->setLabelSize($transport->getLabelSize())
            ->setLabelStartPosition($transport->getLabelStartPosition())
            ->setOrderDataList([$orderData]);

        return $setOrderRequest;
    }

    public function createGetZipCodeRulesRequest() {
        return (new GetZipCodeRulesRequest());
    }
}
