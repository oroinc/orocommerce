<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Order extends Element
{
    /**
     * @param string $title
     */
    public function assertTitle($title)
    {
        $titleElement = $this->findElementContains('OrderTitle', $title);
        self::assertTrue($titleElement->isValid(), sprintf('Title "%s", was not match to current title', $title));
    }
}
