<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Request;

interface ApruveRequestInterface
{
    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return array
     */
    public function getData();
}
