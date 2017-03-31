<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response;

class Response implements ResponseInterface
{
    /**
     * @var \ArrayObject
     */
    protected $values;

    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $this->values = new \ArrayObject($values);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->values;
    }
}
