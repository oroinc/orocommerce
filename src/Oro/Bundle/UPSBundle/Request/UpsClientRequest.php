<?php

namespace Oro\Bundle\UPSBundle\Request;

class UpsClientRequest implements UpsClientRequestInterface
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $requestData;

    /**
     * @param string $url
     * @param array  $requestData
     */
    public function __construct($url, array $requestData)
    {
        $this->url = $url;
        $this->requestData = $requestData;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestData()
    {
        return $this->requestData;
    }
}
