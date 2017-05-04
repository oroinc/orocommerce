<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Shipment;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;

class ApruveShipmentBuilder implements ApruveShipmentBuilderInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var int
     */
    private $amountCents;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $shippedAt;

    /**
     * @param int    $amountCents
     * @param string $currency
     * @param string $shippedAt The ISO8601 date that the shipment was sent.
     */
    public function __construct($amountCents, $currency, $shippedAt)
    {
        $this->amountCents = $amountCents;
        $this->currency = $currency;
        $this->shippedAt = $shippedAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $this->data += [
            ApruveShipment::AMOUNT_CENTS => (int)$this->amountCents,
            ApruveShipment::CURRENCY => (string)$this->currency,
            ApruveShipment::SHIPPED_AT => (string)$this->shippedAt,
        ];

        return new ApruveShipment($this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function setLineItems($lineItems)
    {
        $this->data[ApruveShipment::LINE_ITEMS] = (array)$lineItems;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantShipmentId($shipmentId)
    {
        $this->data[ApruveShipment::MERCHANT_SHIPMENT_ID] = (string)$shipmentId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingCents($amount)
    {
        $this->data[ApruveShipment::SHIPPING_CENTS] = (int)$amount;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTaxCents($amount)
    {
        $this->data[ApruveShipment::TAX_CENTS] = (int)$amount;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantNotes($notes)
    {
        $this->data[ApruveShipment::MERCHANT_NOTES] = (string)$notes;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShipper($shipper)
    {
        $this->data[ApruveShipment::SHIPPER] = (string)$shipper;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTrackingNumber($trackingNumber)
    {
        $this->data[ApruveShipment::TRACKING_NUMBER] = (string)$trackingNumber;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDeliveredAt($deliveredAt)
    {
        $this->data[ApruveShipment::DELIVERED_AT] = (string)$deliveredAt;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setStatus($status)
    {
        $this->data[ApruveShipment::STATUS] = (string)$status;

        return $this;
    }
}
