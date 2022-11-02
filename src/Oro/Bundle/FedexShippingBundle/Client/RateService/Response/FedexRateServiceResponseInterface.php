<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response;

use Oro\Bundle\CurrencyBundle\Entity\Price;

interface FedexRateServiceResponseInterface
{
    public function getSeverityType(): string;

    public function getSeverityCode(): int;

    /**
     * @return Price[]
     */
    public function getPrices(): array;

    public function isSuccessful(): bool;
}
