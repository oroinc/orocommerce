<?php

namespace OroB2B\Bundle\ShippingBundle\Model;

class DimensionsValue
{
    /**
     * @var float
     */
    protected $length;

    /**
     * @var float
     */
    protected $width;

    /**
     * @var float
     */
    protected $height;

    /**
     * @param float $length
     * @param float $width
     * @param float $height
     *
     * @return DimensionsValue
     */
    public static function create($length, $width, $height)
    {
        /* @var $value DimensionsValue */
        $value = new static();
        $value->setLength($length)->setWidth($width)->setHeight($height);

        return $value;
    }

    /**
     * @return float
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param float $length
     *
     * @return $this
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param float $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param float $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !$this->getLength() && !$this->getWidth() && !$this->getHeight();
    }
}
