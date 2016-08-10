<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CheckoutStep extends Element
{
    public function assertTitle($title)
    {
        $currentTitle = $this->find('css', 'h2.checkout__title')->getText();

        if (!preg_match(sprintf('/%s/i', $title), $currentTitle)) {
            self::fail(sprintf('Title "%s", was not match to current title "%s"', $title, $currentTitle));
        }
    }
}
