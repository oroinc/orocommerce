<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CheckoutTotal extends Element
{
    /**
     * @param  string $total
     * @return boolean
     */
    public function isEqual($total)
    {
        return ($total === $this->getText());

    }
}
