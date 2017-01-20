<?php

namespace Oro\Bundle\DPDBundle\Model;


use Oro\Bundle\OrderBundle\Entity\OrderAddress;

class OrderData
{
    /** @var  OrderAddress */
    protected $shipToAddress;

    /** @var  string */
    protected $shipToEmail;

    /** @var  int */
    protected $parcelShopId;

    /** @var  string */
    protected $shipServiceCode;

    /** @var  int */
    protected $weight;

    /** @var  string */
    protected $content;

    /** @var  string */
    protected $yourInternalId;

    /** @var  string */
    protected $reference1;

    /** @var  string */
    protected $reference2;

    /**
     * @return OrderAddress
     */
    public function getShipToAddress()
    {
        return $this->shipToAddress;
    }

    /**
     * @param OrderAddress $shipToAddress
     * @return OrderData
     */
    public function setShipToAddress(OrderAddress $shipToAddress)
    {
        $this->shipToAddress = $shipToAddress;
        return $this;
    }

    /**
     * @return string
     */
    public function getShipToEmail()
    {
        return $this->shipToEmail;
    }

    /**
     * @param string $shipToEmail
     * @return OrderData
     */
    public function setShipToEmail($shipToEmail)
    {
        $this->shipToEmail = $shipToEmail;
        return $this;
    }

    /**
     * @return int
     */
    public function getParcelShopId()
    {
        return $this->parcelShopId;
    }

    /**
     * @param int $parcelShopId
     * @return OrderData
     */
    public function setParcelShopId($parcelShopId)
    {
        $this->parcelShopId = $parcelShopId;
        return $this;
    }

    /**
     * @return string
     */
    public function getShipServiceCode()
    {
        return $this->shipServiceCode;
    }

    /**
     * @param string $shipServiceCode
     * @return OrderData
     */
    public function setShipServiceCode($shipServiceCode)
    {
        $this->shipServiceCode = $shipServiceCode;
        return $this;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     * @return OrderData
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return OrderData
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getYourInternalId()
    {
        return $this->yourInternalId;
    }

    /**
     * @param string $yourInternalId
     * @return OrderData
     */
    public function setYourInternalId($yourInternalId)
    {
        $this->yourInternalId = $yourInternalId;
        return $this;
    }

    /**
     * @return string
     */
    public function getReference1()
    {
        return $this->reference1;
    }

    /**
     * @param string $reference1
     * @return OrderData
     */
    public function setReference1($reference1)
    {
        $this->reference1 = $reference1;
        return $this;
    }

    /**
     * @return string
     */
    public function getReference2()
    {
        return $this->reference2;
    }

    /**
     * @param string $reference2
     * @return OrderData
     */
    public function setReference2($reference2)
    {
        $this->reference2 = $reference2;
        return $this;
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
        $countryIso2Code = $this->shipToAddress->getCountryIso2();
        if ($countryIso2Code === 'US' || $countryIso2Code === 'CA') {
            $state = $this->shipToAddress->getRegionCode();
        }

        $name = $this->shipToAddress->getFirstName() . ' '
            . $this->shipToAddress->getMiddleName() . ' '
            . $this->shipToAddress->getLastName() . ' '
            . $this->shipToAddress->getNameSuffix();
        $name = preg_replace('/ +/', ' ', $name);

        $orderData = [
            'ShipAddress' => [
                'Company' => $this->shipToAddress->getOrganization(),
                'Salutation' => $this->shipToAddress->getNamePrefix(),//FIXME
                'Name' => $name,
                'Street' => $this->shipToAddress->getStreet(),
                'HouseNo' => $this->shipToAddress->getStreet2(),
                'Country' => $countryIso2Code,
                'ZipCode' => $this->shipToAddress->getPostalCode(),
                'City' => $this->shipToAddress->getCity(),
                'State' => $state,
                'Phone' => $this->shipToAddress->getPhone(),
                'Mail' => $this->shipToEmail
            ],
            'ParcelShopID' => $this->parcelShopId,
            'ParcelData' => [
                'ShipService' => $this->shipServiceCode,
                'Weight' => $this->weight,
                'Content' => substr($this->content, 0, 35),
                'YourInternalID' => substr($this->yourInternalId, 0, 35),
                'Reference1' => substr($this->reference1, 0, 35),
                'Reference2' => substr($this->reference2, 0, 35)
            ]
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