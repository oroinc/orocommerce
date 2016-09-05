<?php

namespace Oro\Bundle\UPSBundle\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;

class PriceResponse
{
    const TOTAL_CHARGES = 'TotalCharges';

    /**
     * @var Price[]
     */
    protected $pricesByServices = [];

    /**
     * @param string $string
     * @throws \InvalidArgumentException
     */
    public function parseJSON($string)
    {
        $data = json_decode($string);

        if ($data === null
            || !property_exists($data, 'RateResponse')
            || !property_exists($data->RateResponse, 'RatedShipment')
        ) {
            throw new \InvalidArgumentException('No price data in provided string');
        }

        $this->pricesByServices = [];
        if (is_array($data->RateResponse->RatedShipment)) {
            $this->addRatedShipments($data->RateResponse->RatedShipment);
        } else {
            $this->addRatedShipment($data->RateResponse->RatedShipment);
        }
    }

    /**
     * @param array $rateShipments
     */
    private function addRatedShipments($rateShipments)
    {
        foreach ($rateShipments as $rateShipment) {
            $this->addRatedShipment($rateShipment);
        }
    }

    /**
     * @param \stdClass $rateShipment
     */
    private function addRatedShipment($rateShipment)
    {
        if (property_exists($rateShipment, self::TOTAL_CHARGES)) {
            $price = $this->createPrice($rateShipment->{self::TOTAL_CHARGES});
            if ($price
                && property_exists($rateShipment, 'Service')
                && property_exists($rateShipment->Service, 'Code')
            ) {
                $this->pricesByServices[$rateShipment->Service->Code] = $price;
            }
        }
    }

    /**
     * @param \stdClass $priceData
     * @return Price
     */
    private function createPrice($priceData)
    {
        if (!property_exists($priceData, 'MonetaryValue') || !property_exists($priceData, 'CurrencyCode')) {
            return null;
        }

        return Price::create($priceData->MonetaryValue, $priceData->CurrencyCode);
    }

    /**
     * @return Price[]
     * @throws \InvalidArgumentException
     */
    public function getPricesByServices()
    {
        if (!$this->pricesByServices) {
            throw new \InvalidArgumentException('Response data not loaded');
        }

        return $this->pricesByServices;
    }

    /**
     * @param string $code
     * @throws \InvalidArgumentException
     * @return Price
     */
    public function getPriceByService($code)
    {
        if (!array_key_exists($code, $this->pricesByServices)) {
            throw new \InvalidArgumentException(sprintf('Price data is missing for service %s', $code));
        }

        return $this->pricesByServices[$code];
    }
}
