<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\EntityPage;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class CheckoutPage extends EntityPage
{
    /**
     * {@inheritdoc}
     */
    public function assertPageContainsValue($label, $value)
    {
        /* @var TableRow $rowElement */
        $rowElement = $this->findElementContains('TableRow', $label);
        if (!$rowElement->isIsset()) {
            self::fail(sprintf('Can\'t find "%s" label', $label));
        }

        $cellValueIsValid = $this->spin(function () use ($rowElement, $value) {
            return $rowElement->getCellByNumber(1)->getText() === Form::normalizeValue($value);
        }, 3);

        if (!$cellValueIsValid) {
            self::fail(sprintf('Found "%s" label, but it doesn\'t have "%s" value', $label, $value));
        }
    }
}
