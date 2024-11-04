<?php

namespace Oro\Bundle\UPSBundle\Client\Request;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Base implementation of UPS Client Request
 */
class UpsClientRequest extends ParameterBag implements UpsClientRequestInterface
{
    public const FIELD_URL = 'url';
    public const FIELD_REQUEST_DATA = 'requestData';

    public function __construct(array $params)
    {
        parent::__construct($params);
    }

    #[\Override]
    public function getUrl(): ?string
    {
        return $this->get(self::FIELD_URL);
    }

    #[\Override]
    public function getRequestData(): ?array
    {
        return $this->get(self::FIELD_REQUEST_DATA);
    }
}
