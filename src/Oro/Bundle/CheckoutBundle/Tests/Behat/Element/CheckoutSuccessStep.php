<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CheckoutSuccessStep extends Element
{
    /**
     * @param string $title
     */
    public function assertTitle($title)
    {
        $titleElement = $this->spin(
            function (Element $element) use ($title) {
                $titleElement = $element->findElementContains('CheckoutSuccessStepTitle', $title);

                if ($titleElement->isValid() && $titleElement->isVisible()) {
                    return $titleElement;
                }

                return null;
            },
            30
        );

        self::assertNotNull($titleElement, sprintf('Title "%s", was not found', $title));
    }
}
