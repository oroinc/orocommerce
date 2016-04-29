<?php

namespace OroB2B\Bundle\ShippingBundle\Model;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;

class Dimensions
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
     * @var LengthUnit
     */
    protected $unit;

    /**
     * @param float $length
     * @param float $width
     * @param float $height
     * @param LengthUnit $unit
     *
     * @return Dimensions
     */
    public static function create($length, $width, $height, LengthUnit $unit)
    {
        /* @var $dimensions self */
        $dimensions = new static();
        $dimensions->setLength($length)
            ->setWidth($width)
            ->setHeight($height)
            ->setUnit($unit);

        return $dimensions;
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
     * @return LengthUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param LengthUnit $unit
     *
     * @return $this
     */
    public function setUnit(LengthUnit $unit)
    {
        $this->unit = $unit;

        return $this;
    }
}
