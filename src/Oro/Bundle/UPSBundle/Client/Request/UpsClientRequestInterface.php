<?php

namespace Oro\Bundle\UPSBundle\Client\Request;

/**
 * Interface for UPS Client Request
 */
interface UpsClientRequestInterface
{
    public function getUrl(): ?string;

    public function getRequestData(): ?array;
}
