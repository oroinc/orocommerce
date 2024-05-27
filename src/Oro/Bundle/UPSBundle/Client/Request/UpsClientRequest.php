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

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        return $this->get(self::FIELD_URL);
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestData()
    {
        return $this->get(self::FIELD_REQUEST_DATA);
    }
}
