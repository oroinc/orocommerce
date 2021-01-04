<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ProductAutocomplete extends Element
{
    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->focus();
        parent::setValue($value);
    }
}
