<?php

namespace Oro\Bundle\DPDBundle\Factory;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Model\DPDRequest;
use Oro\Bundle\DPDBundle\Model\GetZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\OrderData;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;


class DPDRequestFactory
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var  PackageProvider */
    protected $packageProvider;

    /** @var MeasureUnitConversion */
    protected $measureUnitConversion;

    /**
     * DPDRequestFactory constructor.
     *
     * @param ManagerRegistry           $registry
     * @param MeasureUnitConversion     $measureUnitConversion
     */
    public function __construct(
        ManagerRegistry $registry,
        PackageProvider $packageProvider,
        MeasureUnitConversion $measureUnitConversion
    ) {
        $this->registry = $registry;
        $this->packageProvider = $packageProvider;
        $this->measureUnitConversion = $measureUnitConversion;
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
        $packages = $this->packageProvider->createFromOrder($order);
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
