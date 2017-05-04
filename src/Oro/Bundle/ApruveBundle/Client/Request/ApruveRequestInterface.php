<?php

namespace Oro\Bundle\ApruveBundle\Client\Request;

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

    /**
     * @return array
     */
    public function toArray();
}
