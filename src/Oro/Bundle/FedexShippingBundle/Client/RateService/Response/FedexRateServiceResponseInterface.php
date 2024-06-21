<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response;

use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Interface for response of FedEx Rate Rest API.
 */
interface FedexRateServiceResponseInterface
{
    public function getResponseStatusCode(): int;

    /**
     * @return Price[]
     */
    public function getPrices(): array;

    /**
     * @return string[]
     */
    public function getErrors(): array;

    public function isSuccessful(): bool;
}
