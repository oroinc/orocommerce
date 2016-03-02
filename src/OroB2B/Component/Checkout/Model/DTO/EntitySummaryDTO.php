<?php

namespace OroB2B\Component\Checkout\Model\DTO;

class EntitySummaryDTO
{
    /** @var  array */
    protected $head;

    /** @var array */
    protected $data;

    /**
     * @param array $head
     * @param array $data
     */
    public function __construct(array $head, array $data)
    {
        $this->head = $head;
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
