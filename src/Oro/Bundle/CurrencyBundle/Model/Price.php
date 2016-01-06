<?php

namespace Oro\Bundle\CurrencyBundle\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Price implements CurrencyAwareInterface
{
    /**
     * @var float
     *
     * @ORM\Column(name="value", type="money", nullable=true)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true)
     */
    protected $currency;

    /**
     * @param float $value
     * @param string $currency
     * @return Price
     */
    public static function create($value, $currency)
    {
        /* @var $price self */
        $price = new static();
        $price->setValue($value)
            ->setCurrency($currency);

        return $price;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }
}
