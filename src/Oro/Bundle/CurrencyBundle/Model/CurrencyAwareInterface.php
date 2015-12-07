<?php
namespace Oro\Bundle\CurrencyBundle\Model;

interface CurrencyAwareInterface
{
    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);
}
