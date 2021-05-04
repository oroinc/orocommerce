<?php

namespace Oro\Bundle\SaleBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\EntityPage;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class QuoteFrontendPage extends EntityPage
{
    /*
     * {@inheritdoc}
     */
    public function assertPageContainsValue($label, $value)
    {
        /* @var TableRow $rowElement */
        $rowElement = $this->findElementContains('TableRow', $label);

        if (!$rowElement->isIsset()) {
            self::fail(sprintf('Can\'t find "%s" label', $label));
        }

        if ($rowElement->getCellByNumber(1)->getText() === Form::normalizeValue($value)) {
            return;
        }

        self::fail(sprintf('Found "%s" label, but it doesn\'t have "%s" value', $label, $value));
    }
}
