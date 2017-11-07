<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response;

use Oro\Bundle\CurrencyBundle\Entity\Price;

interface FedexRateServiceResponseInterface
{
    /**
     * @return string
     */
    public function getSeverityType(): string;

    /**
     * @return int
     */
    public function getSeverityCode(): int;

    /**
     * @return Price|null
     */
    public function getPrice();

    /**
     * @return bool
     */
    public function isSuccessful(): bool;
}
