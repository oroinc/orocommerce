<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Client\Request;

interface ApruveRequestInterface
{
    /**
     * @return string
     */
    public function getUri();

    /**
     * @return array
     */
    public function getData();

    /**
     * @return string
     */
    public function getMethod();
}
