<?php

namespace Oro\Bundle\UPSBundle\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;

class PriceResponse
{
    const TRANSPORTATION_CHARGES = 'TransportationCharges';
    const SERVICE_OPTIONS_CHARGES = 'ServiceOptionsCharges';
    const TOTAL_CHARGES = 'TotalCharges';

    const ALL_SERVICES = [
        self::TRANSPORTATION_CHARGES,
        self::SERVICE_OPTIONS_CHARGES,
        self::TOTAL_CHARGES
    ];

    /**
     * @var array
     */
    protected $pricesByService = [];

    /**
     * @param string $string
     * @throws \InvalidArgumentException
     */
    public function setJSON($string)
    {
        $data = json_decode($string);

        if ($data === null
            || !property_exists($data, 'RateResponse')
            || !property_exists($data->RateResponse, 'RatedShipment')
        ) {
            throw new \InvalidArgumentException('No price data in provided string');
        }

        $this->pricesByService = [];
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
        foreach ($rateShipments as $key => $rateShipment) {
            $this->addRatedShipment($rateShipment);
        }
    }

    /**
     * @param \stdClass $rateShipment
     */
    private function addRatedShipment($rateShipment)
    {
        foreach (self::ALL_SERVICES as $service) {
            if (property_exists($rateShipment, $service)) {
                $price = $this->createPrice($rateShipment->{$service});
                if ($price) {
                    $this->pricesByService[$service][] = $price;
                }
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
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getPricesByServices()
    {
        if (!$this->pricesByService) {
            throw new \InvalidArgumentException('Response data not loaded');
        }

        return $this->pricesByService;
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function getPricesByService($identifier)
    {
        if (!array_key_exists($identifier, $this->pricesByService)) {
            throw new \InvalidArgumentException(sprintf('Price data is missing for service %s', $identifier));
        }

        return $this->pricesByService[$identifier];
    }
}
