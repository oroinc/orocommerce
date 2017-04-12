<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Request;

class ApruveRequest implements ApruveRequestInterface
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var ApruveRequestDataInterface
     */
    private $requestData;

    /**
     * @param string $url
     * @param ApruveRequestDataInterface $requestData
     */
    public function __construct($url, ApruveRequestDataInterface $requestData)
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
    public function getData()
    {
        return $this->requestData->getData();
    }
}
