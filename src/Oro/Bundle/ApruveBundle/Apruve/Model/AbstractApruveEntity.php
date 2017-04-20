<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model;

abstract class AbstractApruveEntity implements ApruveEntityInterface
{
    /**
     * @var array
     */
    protected $data;

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
