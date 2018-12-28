<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TaxBundle\Tests\Behat\Element\TaxBackendOrder;
use Oro\Bundle\TaxBundle\Tests\Behat\Element\TaxBackendOrderLineItem;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class TaxContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Then /^(?:|I )see next line item taxes for backoffice order:$/
     * @param TableNode $table
     */
    public function assertBackendOrderLineItemDiscount(TableNode $table)
    {
        /** @var TaxBackendOrder $order */
        $order = $this->createElement('TaxBackendOrder');

        $taxes = [];

        /** @var TaxBackendOrderLineItem $lineItem */
        foreach ($order->getLineItems() as $lineItem) {
            $taxes[] = array_merge([$lineItem->getProductSKU()], $lineItem->getTaxes());
        }

        $rows = $table->getRows();
        array_shift($rows);

        static::assertEquals($rows, $taxes);
    }
}
