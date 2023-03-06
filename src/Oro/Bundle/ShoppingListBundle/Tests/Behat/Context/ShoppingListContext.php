<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\SubtotalAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class ShoppingListContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @When /^I open page with shopping list (?P<shoppingListLabel>[\w\s\(]+)/
     * @When /^(?:|I )open page with shopping list "(?P<shoppingListLabel>[\w\s\(]+)"$/
     *
     * @param string $shoppingListLabel
     */
    public function openShoppingList($shoppingListLabel): void
    {
        $element = $this->createElement('ShoppingListWidgetContainer');
        $shoppingListItem = $element->findElementContains('ShoppingListWidgetItemName', $shoppingListLabel);
        $shoppingListItem->clickForce();
    }

    /**
     * @When /^I set quantity for shopping list line item with sku "(?P<sku>[\w\d\s]*)" to "(?P<quantity>[\d\.]*)"/
     *
     * @param string $sku
     * @param int|float $quantity
     */
    public function setLineItemQuantity(string $sku, $quantity): void
    {
        $form = $this->getLineItemForm($sku);
        $form->typeInField('Quantity', $quantity);
    }

    /**
     * @When /^I set unit for shopping list line item with sku "(?P<sku>[\w\d\s]*)" to "(?P<unit>[\s\w]*)"/
     */
    public function setLineItemUnit(string $sku, string $unit): void
    {
        $form = $this->getLineItemForm($sku);
        $form->typeInField('Unit', $unit);
    }

    private function getLineItemForm(string $sku): Form
    {
        $shoppingListItem = $this->findElementContains('Shopping list line item', $sku);

        $quantityElement = $shoppingListItem->getElement('Shopping List Line Item Quantity');
        if ($quantityElement->isValid() && $quantityElement->isVisible()) {
            $quantityElement->click();
        }

        return $shoppingListItem->getElement('Shopping List Line Item Form');
    }

    /**
     * @When /^I wait line items are initialized/
     */
    public function waitLineItemsInitialization(): void
    {
        $this->getSession()->getDriver()->wait(30000, "0 != $('input[name=product_qty]:enabled').length");
    }

    /**
     * @Then /^(?:|I )see next subtotals for "(?P<elementName>[\w\s]+)":$/
     * @Then /^(?:|I )see following subtotals for "(?P<elementName>[\w\s]+)":$/
     *
     * @param TableNode $expectedSubtotals
     * @param string $elementName
     */
    public function assertSubtotals(TableNode $expectedSubtotals, $elementName): void
    {
        /** @var SubtotalAwareInterface $element */
        $element = $this->createElement($elementName);

        if (!$element instanceof SubtotalAwareInterface) {
            throw new \InvalidArgumentException(
                sprintf('Element "%s" expected to implement SubtotalsAwareInterface', $elementName)
            );
        }

        $rows = $expectedSubtotals->getRows();
        array_shift($rows);

        foreach ($rows as list($subtotalName, $subtotalAmount)) {
            static::assertEquals(
                $subtotalAmount,
                $element->getSubtotal($subtotalName),
                sprintf(
                    'Wrong value for "%s" subtotal. Expected "%s" got "%s"',
                    $subtotalName,
                    $subtotalAmount,
                    $element->getSubtotal($subtotalName)
                )
            );
        }
    }

    /**
     * @When /^(?:|I )save changes for "(?P<elementName>[^"]+)" row$/
     */
    public function saveChangesForRow(string $elementName): void
    {
        $savedButtonElement = $this->createElement($elementName . ' Save Changes Button');
        $savedButtonElement->click();

        $this->waitForAjax();

        $rowElement = $this->createElement($elementName);

        $className = 'success';
        self::assertTrue(
            $rowElement->hasClass($className),
            sprintf('Element %s was expected to have class %s', $elementName, $className)
        );
    }
}
