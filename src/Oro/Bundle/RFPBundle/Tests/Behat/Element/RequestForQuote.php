<?php

namespace Oro\Bundle\RFPBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class RequestForQuote extends Element
{
    /**
     * @param string $title
     */
    public function assertTitle($title)
    {
        $titleElement = $this->findElementContains('RequestForQuoteTitle', $title);
        self::assertTrue($titleElement->isValid(), sprintf('Title "%s", was not match to current title', $title));
    }
}
