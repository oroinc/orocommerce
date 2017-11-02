<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\FrontendProductGrid;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\FrontendProductGridRow;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FrontendProductGridContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Updates line item form for a given row in frontend product grid.
     * Example: I fill line item form for PSKU1 row in frontend product grid:
     *            | Unit    | item |
     *            | Quanity | 3    |
     *
     * @When /^(?:I |)fill line item form for (?P<content>\S+) row in frontend product grid with:$/
     *
     * @param string $content
     * @param TableNode $values
     */
    public function fillLineItemFormForProductRow(string $content, TableNode $values)
    {
        /** @var FrontendProductGrid $frontendProductGrid */
        $frontendProductGrid = $this->elementFactory->createElement('ProductFrontendGrid');

        /** @var FrontendProductGridRow $gridRow */
        $gridRow = $frontendProductGrid->getRowByContent($content);

        /** @var Form $lineItemForm */
        $lineItemForm = $this->elementFactory->createElement('FrontendLineItemForm', $gridRow);
        $lineItemForm->fill($values);
    }
}
