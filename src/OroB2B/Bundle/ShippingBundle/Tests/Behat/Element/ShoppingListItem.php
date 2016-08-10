<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Behat\Elements;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ShoppingListItem extends Element
{
    public function viewDetails()
    {
        $this->clickLink('View Details');
    }
}
