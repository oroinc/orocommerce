<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request;

/**
 * FedEx Rest API request configuration.
 */
class FedexRequest implements FedexRequestInterface
{
    private array $requestData;
    private string $uri;
    private bool $isCheckMode;

    public function __construct(string $uri, array $requestData = [], bool $isCheckMode = false)
    {
        $this->requestData = $requestData;
        $this->uri = $uri;
        $this->isCheckMode = $isCheckMode;
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
