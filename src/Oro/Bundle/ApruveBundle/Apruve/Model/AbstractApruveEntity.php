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
        return (array)$this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if (array_key_exists('id', $this->data)) {
            return (string) $this->data['id'];
        }

        return null;
    }
}
