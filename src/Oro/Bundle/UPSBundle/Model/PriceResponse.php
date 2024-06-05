<?php

namespace Oro\Bundle\UPSBundle\Model;

use InvalidArgumentException;
use LogicException;
use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * UPS Price Response model
 */
class PriceResponse
{
    public const TOTAL_CHARGES = 'TotalCharges';

    /** @var Price[] */
    protected array $pricesByServices = [];

    /**
     * @param array $data
     * @return $this
     * @throws InvalidArgumentException
     * @throws LogicException on UPS fault
     */
    public function parse(array $data): static
    {
        if ($data && array_key_exists('Fault', $data)) {
            throw new LogicException(json_encode($data['Fault']));
        }

        if (!array_key_exists('RateResponse', $data)
            || !array_key_exists('RatedShipment', $data['RateResponse'])
        ) {
            throw new InvalidArgumentException('No price data in provided string.');
        }

        $this->pricesByServices = [];

        if (array_key_exists(self::TOTAL_CHARGES, $data['RateResponse']['RatedShipment'])) {
            $this->addRatedShipment($data['RateResponse']['RatedShipment']);
        } else {
            $this->addRatedShipments($data['RateResponse']['RatedShipment']);
        }

        return $this;
    }

    private function addRatedShipments(array $rateShipments): void
    {
        foreach ($rateShipments as $rateShipment) {
            $this->addRatedShipment($rateShipment);
        }
    }

    private function addRatedShipment(array $rateShipment): void
    {
        if (array_key_exists(self::TOTAL_CHARGES, $rateShipment)) {
            $price = $this->createPrice($rateShipment[self::TOTAL_CHARGES]);
            if ($price && $rateShipment['Service'] && $rateShipment['Service']['Code']) {
                $this->pricesByServices[$rateShipment['Service']['Code']] = $price;
            }
        }
    }

    private function createPrice(array $priceData): ?Price
    {
        if (!array_key_exists('MonetaryValue', $priceData) ||
            !array_key_exists('CurrencyCode', $priceData)) {
            return null;
        }

        return Price::create($priceData['MonetaryValue'], $priceData['CurrencyCode']);
    }

    /**
     * @return Price[]
     *
     * @throws InvalidArgumentException
     */
    public function getPricesByServices(): array
    {
        if (!$this->pricesByServices) {
            throw new InvalidArgumentException('Response data not loaded');
        }

        return $this->pricesByServices;
    }

    /**
     * @param string $code
     * @return Price|null
     *
     * @throws InvalidArgumentException
     */
    public function getPriceByService(string $code): ?Price
    {
        if (!array_key_exists($code, $this->pricesByServices)) {
            return null;
        }

        return $this->pricesByServices[$code];
    }
}
