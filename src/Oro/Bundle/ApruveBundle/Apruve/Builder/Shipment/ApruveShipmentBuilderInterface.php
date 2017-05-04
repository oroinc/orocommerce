<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Shipment;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;

interface ApruveShipmentBuilderInterface
{
    /**
     * @return ApruveShipment
     */
    public function getResult();

    /**
     * @param array $lineItems
     *
     * @return self
     */
    public function setLineItems($lineItems);

    /**
     * @param int $amount
     *
     * @return self
     */
    public function setTaxCents($amount);

    /**
     * @param int $amount
     *
     * @return self
     */
    public function setShippingCents($amount);

    /**
     * @param string $shipper
     *
     * @return self
     */
    public function setShipper($shipper);

    /**
     * @param string $trackingNumber
     *
     * @return self
     */
    public function setTrackingNumber($trackingNumber);

    /**
     * The ISO8601 date which represents when the shipment was delivered to the customer.
     *
     * @param string $deliveredAt
     *
     * @return self
     */
    public function setDeliveredAt($deliveredAt);

    /**
     * The status of the shipment (defaults to fulfilled).
     *
     * Indicates if this shipment fulfills all the items on the invoice. The other option is 'PARTIAL'.
     *
     * @param string $status
     *
     * @return self
     */
    public function setStatus($status);

    /**
     * @param string|int $shipmentId
     *
     * @return self
     */
    public function setMerchantShipmentId($shipmentId);

    /**
     * @param string $notes
     *
     * @return self
     */
    public function setMerchantNotes($notes);
}
