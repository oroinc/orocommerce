<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response;

use Oro\Bundle\CurrencyBundle\Entity\Price;

interface FedexRateServiceResponseInterface
{
    /**
     * @return string
     */
    public function getSeverityCode(): string;

    /**
     * @return string
     */
    public function getSeverityMessage(): string;

    /**
     * @return Price[]
     */
    public function getPrices(): array;
}
