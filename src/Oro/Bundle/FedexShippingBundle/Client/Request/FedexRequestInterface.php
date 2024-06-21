<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request;

/**
 * Interface for FedEx Rest API request configuration.
 */
interface FedexRequestInterface
{
    public function getRequestData(): array;

    public function getUri(): string;

    public function isCheckMode(): bool;
}
