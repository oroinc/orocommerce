<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Shipment;

use Oro\Bundle\ApruveBundle\Apruve\Builder\Shipment\ApruveShipmentBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;

class ApruveShipmentBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mandatory
     */
    const AMOUNT_CENTS = 11130;
    const CURRENCY = 'USD';
    const LINE_ITEMS = [
        'sku1' => [
            'sku' => 'sku1',
            'quantity' => 100,
            'currency' => 'USD',
            'amount_cents' => 2000,
        ],
        'sku2' => [
            'sku' => 'sku2',
            'quantity' => 50,
            'currency' => 'USD',
            'amount_cents' => 1000,
        ],
    ];

    /**
     * Optional
     */
    const SHIPPING_AMOUNT_CENTS = 1010;
    const TAX_AMOUNT_CENTS = 110;
    const SHIPPER = 'Sample Shipper Name';
    const TRACKING_NUMBER = 'sampleTrackingNumber';
    const STATUS = 'sampleStatus';
    const MERCHANT_SHIPMENT_ID = '123';
    const MERCHANT_NOTES = 'Sample merchant notes';
    const SHIPPED_AT_STRING = '2027-04-15T10:12:27-05:00';
    const DELIVERED_AT_STRING = '2027-04-15T10:12:27-05:00';

    /**
     * @var ApruveShipmentBuilder
     */
    private $builder;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->builder = new ApruveShipmentBuilder(
            self::AMOUNT_CENTS,
            self::CURRENCY,
            self::SHIPPED_AT_STRING
        );
    }

    public function testGetResult()
    {
        $actual = $this->builder->getResult();

        $expected = [
            ApruveShipment::AMOUNT_CENTS => self::AMOUNT_CENTS,
            ApruveShipment::CURRENCY => self::CURRENCY,
            ApruveShipment::SHIPPED_AT => self::SHIPPED_AT_STRING,
        ];
        static::assertEquals($expected, $actual->getData());
    }

    public function testGetResultWithOptionalParams()
    {
        $this->builder->setLineItems(self::LINE_ITEMS);
        $this->builder->setTaxCents(self::TAX_AMOUNT_CENTS);
        $this->builder->setShippingCents(self::SHIPPING_AMOUNT_CENTS);
        $this->builder->setShipper(self::SHIPPER);
        $this->builder->setTrackingNumber(self::TRACKING_NUMBER);
        $this->builder->setDeliveredAt(self::DELIVERED_AT_STRING);
        $this->builder->setStatus(self::STATUS);
        $this->builder->setMerchantShipmentId(self::MERCHANT_SHIPMENT_ID);
        $this->builder->setMerchantNotes(self::MERCHANT_NOTES);

        $actual = $this->builder->getResult();

        $expected = [
            ApruveShipment::AMOUNT_CENTS => self::AMOUNT_CENTS,
            ApruveShipment::CURRENCY => self::CURRENCY,
            ApruveShipment::LINE_ITEMS => self::LINE_ITEMS,
            ApruveShipment::TAX_CENTS => self::TAX_AMOUNT_CENTS,
            ApruveShipment::SHIPPING_CENTS => self::SHIPPING_AMOUNT_CENTS,
            ApruveShipment::SHIPPER => self::SHIPPER,
            ApruveShipment::TRACKING_NUMBER => self::TRACKING_NUMBER,
            ApruveShipment::SHIPPED_AT => self::SHIPPED_AT_STRING,
            ApruveShipment::DELIVERED_AT => self::DELIVERED_AT_STRING,
            ApruveShipment::STATUS => self::STATUS,
            ApruveShipment::MERCHANT_SHIPMENT_ID => self::MERCHANT_SHIPMENT_ID,
            ApruveShipment::MERCHANT_NOTES => self::MERCHANT_NOTES,
        ];
        static::assertEquals($expected, $actual->getData());
    }
}
