<?php

namespace Oro\Bundle\DPDBundle\Model;

// FIXME: Cleanup/remove file
class PriceTable
{
    /** @var  array */
    protected $table;

    /**
     * PriceTable constructor.
     */
    public function __construct()
    {
        $this->table = array();
    }

    /**
     * @param $key
     * @return bool
     */
    public function hasKey($key)
    {
        return array_key_exists($key, $this->table);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if ($this->hasKey($key)) {
            return $this->table[$key];
        }

        return $default;
    }

    /**
     * @param $data
     */
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