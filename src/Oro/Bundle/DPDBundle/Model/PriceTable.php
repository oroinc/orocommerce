<?php

namespace Oro\Bundle\DPDBundle\Model;


class PriceTable
{
    /** @var  array */
    protected $table;

    public function __construct()
    {
        $this->table = array();
    }

    public function hasKey($key)
    {
        return array_key_exists($key, $this->table);
    }

    public function get($key, $default = null)
    {
        if ($this->hasKey($key)) {
            return $this->table[$key];
        }
        return $default;
    }

    //FIXME: very minimalistic implementation...
    public function fromArray($data)
    {
        $this->table = array();
        foreach ($data as $item) {
            $splitedItem = explode(',', $item);
            if (count($splitedItem) >= 2) {
                $key = trim($splitedItem[0]);
                $value = trim($splitedItem[1]);
                $this->table[$key] = $value;
            }
        }
    }
}