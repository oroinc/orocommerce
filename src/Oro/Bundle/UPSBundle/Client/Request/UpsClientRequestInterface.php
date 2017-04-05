<?php

namespace Oro\Bundle\UPSBundle\Client\Request;

interface UpsClientRequestInterface
{
    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return array
     */
    public function getRequestData();
}
