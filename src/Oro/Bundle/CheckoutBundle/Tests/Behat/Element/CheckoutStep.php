<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CheckoutStep extends Element
{
    public function assertTitle($title)
    {
        $currentTitle = $this->getElement('CheckoutStepTitle');
        self::assertTrue($currentTitle->isValid(), 'Checkout step title not found, maybe you are on another page?');

        $currentTitleText = $currentTitle->getText();
        self::assertContains(
            $title,
            $currentTitleText,
            sprintf('Expected title "%s", does not contains in "%s" current title', $title, $currentTitleText)
        );
    }
}
