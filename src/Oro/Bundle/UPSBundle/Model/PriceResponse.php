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
     * @param array $data
     * @throws \InvalidArgumentException
     */
    public function parse($data)
    {
        if ($data === null ||
            !array_key_exists('RateResponse', $data) ||
            !array_key_exists('RatedShipment', $data['RateResponse'])) {
            throw new \InvalidArgumentException('No price data in provided string.');
        }

        $this->pricesByServices = [];

        if (array_key_exists(self::TOTAL_CHARGES, $data['RateResponse']['RatedShipment'])) {
            $this->addRatedShipment($data['RateResponse']['RatedShipment']);
        } else {
            $this->addRatedShipments($data['RateResponse']['RatedShipment']);
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
     * @param array $rateShipment
     */
    private function addRatedShipment(array $rateShipment)
    {
        if (array_key_exists(self::TOTAL_CHARGES, $rateShipment)) {
            $price = $this->createPrice($rateShipment[self::TOTAL_CHARGES]);
            if ($price && $rateShipment['Service'] && $rateShipment['Service']['Code']) {
                $this->pricesByServices[$rateShipment['Service']['Code']] = $price;
            }
        }
    }

    /**
     * @param array $priceData
     * @return Price
     */
    private function createPrice($priceData)
    {
        if (!array_key_exists('MonetaryValue', $priceData) ||
            !array_key_exists('CurrencyCode', $priceData)) {
            return null;
        }

        return Price::create($priceData['MonetaryValue'], $priceData['CurrencyCode']);
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
