<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Price extends \Oro\Bundle\CurrencyBundle\Model\Price
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
}
