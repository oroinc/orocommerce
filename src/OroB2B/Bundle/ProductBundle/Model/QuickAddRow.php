<?php

namespace OroB2B\Bundle\ProductBundle\Model;

class QuickAddRow
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $sku;

    /**
     * @var float
     */
    protected $quantity;

    /**
     * @var bool
     */
    protected $valid = false;

    /**
     * @var bool
     */
    protected $complete = false;

    /**
     * @param int $id
     * @param string $sku
     * @param float $quantity
     */
    public function __construct($id, $sku, $quantity)
    {
        $this->id = $id;
        $this->sku = $sku;
        $this->quantity = $quantity;

        if ($sku && $quantity) {
            $this->complete = true;
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    /**
     * @return bool
     */
    public function isComplete()
    {
        return $this->complete;
    }
}
