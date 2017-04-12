<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Request;

class ApruveRequestData implements ApruveRequestDataInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return (array) $this->data;
    }
}
