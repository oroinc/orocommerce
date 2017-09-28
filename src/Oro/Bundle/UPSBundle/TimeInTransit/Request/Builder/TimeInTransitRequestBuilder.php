<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequest;

class TimeInTransitRequestBuilder implements TimeInTransitRequestBuilderInterface
{
    const WEIGHT_KGS = 'KGS';
    const WEIGHT_LBS = 'LBS';

    /**
     * @internal
     */
    const REQUEST_URL = 'TimeInTransit';

    /**
     * @internal
     */
    const REQUEST_OPTION = 'TNT';

    /**
     * @var string
     */
    private $upsApiUsername;

    /**
     * @var string
     */
    private $upsApiPassword;

    /**
     * @var string
     */
    private $upsApiKey;

    /**
     * Customer provided data.
     * If this data is present in the request, it is echoed back to the customer.
     * Optional.
     *
     * @var string
     */
    private $customerContext;

    /**
     * Customer provided data.
     * If this data is present in the request, it is echoed back to the customer.
     * Optional.
     *
     * @var string
     */
    private $transactionIdentifier;

    /**
     * Address for the origin of the shipment.
     * Required.
     *
     * @var AddressInterface
     */
    private $shipFromAddress;

    /**
     * Address for the destination of the shipment.
     * Required.
     *
     * @var AddressInterface
     */
    private $shipToAddress;

    /**
     * Required when weight is provided.
     * Valid values: LBS = Pounds, KGS = Kilograms
     *
     * @var string
     */
    private $weightUnitCode;

    /**
     * Weight of the package.
     * Required for international requests.
     * Required for US Domestic queries if non-document is indicated. Cannot exceed 150 LBS (70KGS)
     *
     * @var string
     */
    private $weight;

    /**
     * Shipment Date Query cannot exceed 35 days into the past or 60 days into the future.
     * Required.
     *
     * @var \DateTime
     */
    private $pickupDate;

    /**
     * Allows for the user to input the number candidates he/she wishes to receive.
     * Valid values are 1 through 50.
     * If the user chooses not to provide a value, the default value is 35.
     * Optional.
     *
     * @var string
     */
    private $maximumListSize;

    /**
     * @param string           $upsApiUsername
     * @param string           $upsApiPassword
     * @param string           $upsApiKey
     * @param AddressInterface $shipFromAddress
     * @param AddressInterface $shipToAddress
     * @param \DateTime        $pickupDate
     */
    public function __construct(
        $upsApiUsername,
        $upsApiPassword,
        $upsApiKey,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    ) {
        $this->shipFromAddress = $shipFromAddress;
        $this->shipToAddress = $shipToAddress;
        $this->pickupDate = $pickupDate;
        $this->upsApiUsername = $upsApiUsername;
        $this->upsApiPassword = $upsApiPassword;
        $this->upsApiKey = $upsApiKey;
    }

    /**
     * {@inheritDoc}
     */
    public function createRequest()
    {
        $requestData = $this->getRequestData();

        return new UpsClientRequest([
            UpsClientRequest::FIELD_URL => self::REQUEST_URL,
            UpsClientRequest::FIELD_REQUEST_DATA => $requestData,
        ]);
    }

    /**
     * @return array
     */
    private function getRequestData()
    {
        $request = [
            'Security' => [
                'UsernameToken' => [
                    'Username' => $this->upsApiUsername,
                    'Password' => $this->upsApiPassword,
                ],
                'UPSServiceAccessToken' => [
                    'AccessLicenseNumber' => $this->upsApiKey,
                ],
            ],
            'TimeInTransitRequest' => [
                'Request' => [
                    'RequestOption' => static::REQUEST_OPTION,
                ],
                'ShipFrom' => [
                    'Address' => [
                        'StateProvinceCode' => $this->shipFromAddress->getRegionCode(),
                        'PostalCode' => (string)$this->shipFromAddress->getPostalCode(),
                        'CountryCode' => $this->shipFromAddress->getCountryIso2(),
                    ],
                ],
                'ShipTo' => [
                    'Address' => [
                        'StateProvinceCode' => $this->shipToAddress->getRegionCode(),
                        'PostalCode' => (string)$this->shipToAddress->getPostalCode(),
                        'CountryCode' => $this->shipToAddress->getCountryIso2(),
                    ],
                ],
                'Pickup' => [
                    'Date' => $this->pickupDate->format('Ymd'),
                ],
            ],
        ];

        if ($this->customerContext !== null) {
            $request['TimeInTransitRequest']['Request']['TransactionReference']['CustomerContext']
                = $this->customerContext;
        }

        if ($this->transactionIdentifier !== null) {
            $request['TimeInTransitRequest']['Request']['TransactionReference']['TransactionIdentifier']
                = $this->transactionIdentifier;
        }

        if ($this->weight !== null) {
            $request['TimeInTransitRequest'] += [
                'ShipmentWeight' => [
                    'UnitOfMeasurement' => [
                        'Code' => (string)$this->weightUnitCode,
                    ],
                    'Weight' => (string)$this->weight,
                ],
            ];
        }

        if ($this->maximumListSize !== null) {
            $request['TimeInTransitRequest'] += [
                'MaximumListSize' => (string)$this->maximumListSize,
            ];
        }

        return $request;
    }

    /**
     * {@inheritDoc}
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setWeightUnitCode($weightUnitCode)
    {
        $this->weightUnitCode = $weightUnitCode;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMaximumListSize($maximumListSize)
    {
        $this->maximumListSize = $maximumListSize;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTransactionIdentifier($transactionIdentifier)
    {
        $this->transactionIdentifier = $transactionIdentifier;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomerContext($customerContext)
    {
        $this->customerContext = $customerContext;

        return $this;
    }
}
