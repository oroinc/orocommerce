<?php

namespace Oro\Bundle\FedexShippingBundle\AddressValidation\Client\Request;

use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequestInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;

/**
 * Request of FedEx Address Validation Rest API.
 */
class FedexAddressValidationRequest implements AddressValidationRequestInterface, FedexRequestInterface
{
    public function __construct(
        private string $uri,
        private array $requestData = [],
        private bool $isCheckMode = false
    ) {
    }

    #[\Override]
    public function getUri(): string
    {
        return $this->uri;
    }

    #[\Override]
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    #[\Override]
    public function isCheckMode(): bool
    {
        return $this->isCheckMode;
    }
}
