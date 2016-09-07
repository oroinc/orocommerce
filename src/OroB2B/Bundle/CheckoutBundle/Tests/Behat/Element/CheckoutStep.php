<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CheckoutStep extends Element
{
    public function assertTitle($title)
    {
        $titleElement = $this->findElementContains('CheckoutStepTitle', $title);
        self::assertTrue($titleElement->isValid(), sprintf('Title "%s", was not match to current title', $title));
    }
}
