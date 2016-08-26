<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ShoppingListItem extends Element
{
    public function viewDetails()
    {
        $this->clickLink('View Details');
    }
}
