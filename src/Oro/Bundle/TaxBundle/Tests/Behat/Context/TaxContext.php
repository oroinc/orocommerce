<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TaxBundle\Tests\Behat\Element\TaxBackendOrder;
use Oro\Bundle\TaxBundle\Tests\Behat\Element\TaxBackendOrderDraftEditLineItem;
use Oro\Bundle\TaxBundle\Tests\Behat\Element\TaxBackendOrderLineItem;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class TaxContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Then /^(?:|I )see next line item taxes for backoffice order:$/
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

    /**
     * Example: Then I see the next line item taxes for backoffice order edit for "SKU1":
     *      |            | Incl. Tax | Excl. Tax | Tax Amount |
     *      | Unit Price | €40.00    | €40.00    | €0.00      |
     *      | Row Total  | €40.00    | €40.00    | €0.00      |
     *
     * @Then /^(?:|I )see the next line item taxes for backoffice order edit for "([^"]+)":$/
    */
    public function checkLineItemTaxesInOrderEditForSku(string $sku, TableNode $table): void
    {
        $rows = $table->getRows();
        array_shift($rows);

        $actualTaxes = $this->spin(function () use ($sku) {
            return $this->findLineItemBySku($sku)->getTaxes();
        });

        self::assertNotNull($actualTaxes, sprintf('Unable to get taxes for line item with SKU "%s"', $sku));
        self::assertEquals($rows, $actualTaxes);
    }

    /**
     * Example: And I see the next line item tax results for backoffice order edit for "SKU1":
     *      | Tax          | Rate | Taxable Amount | Tax Amount |
     *      | berlin_sales | 9%   | €40.00         | €3.60      |
     *
     * @Then /^(?:|I )see the next line item tax results for backoffice order edit for "([^"]+)":$/
     */
    public function checkLineItemTaxResultsInOrderEditForSku(string $sku, TableNode $table): void
    {
        $rows = $table->getRows();
        array_shift($rows);

        $actualTaxResults = $this->spin(function () use ($sku) {
            return $this->findLineItemBySku($sku)->getTaxResults();
        });

        self::assertNotNull(
            $actualTaxResults,
            sprintf('Unable to get tax results for line item with SKU "%s"', $sku)
        );
        static::assertEquals($rows, $actualTaxResults);
    }

    private function findLineItemBySku(string $sku): TaxBackendOrderDraftEditLineItem
    {
        /** @var TaxBackendOrderDraftEditLineItem $lineItem */
        foreach ($this->findAllElements('TaxBackendOrderDraftEditLineItem') as $lineItem) {
            if ($lineItem->getProductSKU() === $sku) {
                return $lineItem;
            }
        }

        self::fail(sprintf('Line item with SKU "%s" not found in edit grid', $sku));
    }
}
