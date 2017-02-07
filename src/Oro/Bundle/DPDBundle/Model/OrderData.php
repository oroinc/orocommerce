<?php

namespace Oro\Bundle\DPDBundle\Model;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Symfony\Component\HttpFoundation\ParameterBag;

class OrderData extends ParameterBag
{
    const FIELD_SHIP_TO_ADDRESS = 'ship_to_address';
    const FIELD_SHIP_TO_EMAIL = 'ship_to_email';
    const FIELD_PARCEL_SHOP_ID = 'parcel_shop_id';
    const FIELD_SHIP_SERVICE_CODE = 'ship_service_code';
    const FIELD_WEIGHT = 'weight';
    const FIELD_CONTENT = 'content';
    const FIELD_YOUR_INTERNAL_ID = 'your_internal_id';
    const FIELD_REFERENCE1 = 'reference1';
    const FIELD_REFERENCE2 = 'reference2';

    const DPD_API_SHIP_ADDRESS_COMPANY_MAX_LENGTH = 35;
    const DPD_API_SHIP_ADDRESS_SALUTATION_MAX_LENGTH = 10;
    const DPD_API_SHIP_ADDRESS_NAME_MAX_LENGTH = 35;
    const DPD_API_SHIP_ADDRESS_STREET_MAX_LENGTH = 35;
    const DPD_API_SHIP_ADDRESS_HOUSE_NO_MAX_LENGTH = 8;
    const DPD_API_SHIP_ADDRESS_ZIP_CODE_MAX_LENGTH = 8;
    const DPD_API_SHIP_ADDRESS_CITY_MAX_LENGTH = 35;
    const DPD_API_SHIP_ADDRESS_PHONE_MAX_LENGTH = 20;
    const DPD_API_PARCEL_DATA_CONTENT_MAX_LENGTH = 35;
    const DPD_API_PARCEL_DATA_YOUR_INTERNAL_ID_MAX_LENGTH = 35;
    const DPD_API_PARCEL_DATA_REFERENCE1_MAX_LENGTH = 35;
    const DPD_API_PARCEL_DATA_REFERENCE2_MAX_LENGTH = 35;

    public function __construct(array $params)
    {
        parent::__construct($params);
    }

    /**
     * @return OrderAddress
     */
    public function getShipToAddress()
    {
        return $this->get(self::FIELD_SHIP_TO_ADDRESS);
    }

    /**
     * @return string
     */
    public function getShipToEmail()
    {
        return $this->get(self::FIELD_SHIP_TO_EMAIL);
    }

    /**
     * @return int
     */
    public function getParcelShopId()
    {
        return $this->get(self::FIELD_PARCEL_SHOP_ID);
    }

    /**
     * @return string
     */
    public function getShipServiceCode()
    {
        return $this->get(self::FIELD_SHIP_SERVICE_CODE);
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->get(self::FIELD_WEIGHT);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->get(self::FIELD_CONTENT);
    }

    /**
     * @return string
     */
    public function getYourInternalId()
    {
        return $this->get(self::FIELD_YOUR_INTERNAL_ID);
    }

    /**
     * @return string
     */
    public function getReference1()
    {
        return $this->get(self::FIELD_REFERENCE1);
    }

    /**
     * @return string
     */
    public function getReference2()
    {
        return $this->get(self::FIELD_REFERENCE2);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        /**
         * From DPD Cloud service documentation:
         *      If „USA“ or „CAN“ is selected as country, state is
         *      mandatory! For all other countries, it is not allowed
         *      to specify a state!
         */
        $state = '';
        $countryIso2Code = $this->getShipToAddress()->getCountryIso2();
        if ($countryIso2Code === 'US' || $countryIso2Code === 'CA') {
            $state = $this->getShipToAddress()->getRegionCode();
        }

        $name = $this->getShipToAddress()->getFirstName().' '
            .$this->getShipToAddress()->getMiddleName().' '
            .$this->getShipToAddress()->getLastName().' '
            .$this->getShipToAddress()->getNameSuffix();
        $name = preg_replace('/ +/', ' ', $name);

        $orderData = [
            'ShipAddress' => [
                'Company' => substr(
                    $this->getShipToAddress()->getOrganization(),
                    0,
                    self::DPD_API_SHIP_ADDRESS_COMPANY_MAX_LENGTH
                ),
                'Salutation' => substr(
                    $this->getShipToAddress()->getNamePrefix(),
                    0,
                    self::DPD_API_SHIP_ADDRESS_SALUTATION_MAX_LENGTH
                ),
                'Name' => substr(
                    $name,
                    0,
                    self::DPD_API_SHIP_ADDRESS_NAME_MAX_LENGTH
                ),
                'Street' => substr(
                    $this->getShipToAddress()->getStreet(),
                    0,
                    self::DPD_API_SHIP_ADDRESS_STREET_MAX_LENGTH
                ),
                //FIXME: we can't ensure this is the House number
                'HouseNo' => substr(
                    $this->getShipToAddress()->getStreet2(),
                    0,
                    self::DPD_API_SHIP_ADDRESS_HOUSE_NO_MAX_LENGTH
                ),
                'Country' => $countryIso2Code,
                'ZipCode' => substr(
                    $this->getShipToAddress()->getPostalCode(),
                    0,
                    self::DPD_API_SHIP_ADDRESS_ZIP_CODE_MAX_LENGTH
                ),
                'City' => substr(
                    $this->getShipToAddress()->getCity(),
                    0,
                    self::DPD_API_SHIP_ADDRESS_CITY_MAX_LENGTH
                ),
                'State' => $state,
                'Phone' => substr(
                    $this->getShipToAddress()->getPhone(),
                    0,
                    self::DPD_API_SHIP_ADDRESS_PHONE_MAX_LENGTH
                ),
                'Mail' => $this->getShipToEmail(),
            ],
            'ParcelShopID' => $this->getParcelShopId(),
            'ParcelData' => [
                'ShipService' => $this->getShipServiceCode(),
                'Weight' => $this->getWeight(),
                'Content' => substr(
                    $this->getContent(),
                    0,
                    self::DPD_API_PARCEL_DATA_CONTENT_MAX_LENGTH
                ),
                'YourInternalID' => substr(
                    $this->getYourInternalId(),
                    0,
                    self::DPD_API_PARCEL_DATA_YOUR_INTERNAL_ID_MAX_LENGTH
                ),
                'Reference1' => substr(
                    $this->getReference1(),
                    0,
                    self::DPD_API_PARCEL_DATA_REFERENCE1_MAX_LENGTH
                ),
                'Reference2' => substr(
                    $this->getReference2(),
                    0,
                    self::DPD_API_PARCEL_DATA_REFERENCE2_MAX_LENGTH
                ),
            ],
        ];

        return $orderData;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
