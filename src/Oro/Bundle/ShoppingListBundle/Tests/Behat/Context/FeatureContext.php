<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Oro\Bundle\RFPBundle\Tests\Behat\Element\RequestForQuote;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ConfigurableProductTableRowAwareInterface;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ProductTable;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ProductTableRow;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ShoppingList as ShoppingListElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * The context for testing Shopping List related features.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @When /^Buyer is on "(?P<shoppingListLabel>[\w\s]+)" shopping list$/
     *
     * @param string $shoppingListLabel
     */
    public function buyerIsOnShoppingList($shoppingListLabel)
    {
        $shoppingList = $this->getShoppingListByLabel($shoppingListLabel);
        $this->visitPath($this->getShoppingListViewUrl($shoppingList));
        $this->waitForAjax();

        /* @var ShoppingListElement $element */
        $element = $this->createElement('ShoppingList');
        $element->assertTitle($shoppingListLabel);
    }

    /**
     * @When There it Requested a quote
     */
    public function buyerIsRequestedAQuote()
    {
        $this->getSession()->getPage()->clickLink('Request Quote');
        $this->waitForAjax();

        /* @var RequestForQuote $page */
        $page = $this->createElement('RequestForQuote');
        $page->assertTitle('Request A Quote');
        $this->waitForAjax();

        $this->getSession()->getPage()->pressButton('Submit Request');
        $this->waitForAjax();
    }

    /**
     * @Then /^it on page Request For Quote and see message (?P<message>[\w\s]+)$/
     *
     * @param string $message
     */
    public function buyerIsViewRequestForQuote($message)
    {
        /* @var RequestForQuote $page */
        $page = $this->createElement('RequestForQuote');
        $page->assertTitle('Request For Quote');

        $element = $this->findElementContains('RequestForQuoteFlashMessage', $message);
        $this->assertTrue($element->isValid(), sprintf('Title "%s", was not match to current title', $message));
    }

    /**
     * Finds the delete button for a line item by its row number for the given shopping list and clicks it.
     *
     * Example: When I delete line item 1 in "Shopping List Line Items Table"
     *
     * @param integer   $itemPosition
     * @param string    $shoppingList
     *
     * @When I delete line item :itemPosition in :shoppingList
     */
    public function iClickDeleteLineItemNumberIn($itemPosition, $shoppingList)
    {
        /** @var Table $shoppingListItemsTableElement */
        $shoppingListItemsTableElement = $this->elementFactory->createElement($shoppingList);
        self::assertTrue(
            $shoppingListItemsTableElement->isValid(),
            sprintf('Element "%s" was not found', $shoppingList)
        );

        $rows = $this->getShoppingListLineItemsTableDirectRows($shoppingListItemsTableElement);
        /** @var TableRow $row */
        $row = $rows[$itemPosition - 1];
        $button = $row->find('css', 'button.item-remove');

        $button->click();
    }

    /**
     * @param string    $shoppingList
     * @param TableNode $table
     *
     * @When I should see following line items in :arg1:
     */
    public function iShouldSeeFollowingLineItemsIn($shoppingList, TableNode $table)
    {
        /** @var Table $shoppingListItemsTableElement */
        $shoppingListItemsTableElement = $this->createValidShoppingListTableElement($shoppingList);

        $rows = $this->getShoppingListLineItemsTableDirectRows($shoppingListItemsTableElement);
        $tableRows = $table->getRows();
        $columnHeaders = reset($tableRows);

        $actualRows = [];
        foreach ($rows as $rowElement) {
            $actualRows[] = $this->getLineItemRowColumnsValues($rowElement, $columnHeaders);
        }

        self::assertEquals($table->getHash(), $actualRows);
    }

    /**
     * @When /^(?:|I )should see following header in shopping list line items table:$/
     */
    public function iShouldSeeFollowingColumns(TableNode $table)
    {
        $rows = $table->getRows();
        self::assertNotEmpty($rows);

        /* @var ShoppingListElement $element */
        $element = $this->createElement('ShoppingList');

        self::assertEquals(reset($rows), $element->getLineItemsHeader());
    }

    /**
     * @Then I open shopping list widget
     * @Then I close shopping list widget
     */
    public function iOpenShoppingListWidget()
    {
        $this->createElement('ShoppingListWidget')->click();
    }

    /**
     * Opens shopping list from widget
     * Example: And I click "Shopping List 1" on shopping list widget
     *
     * @Given /^(?:|I )click "(?P<name>[\w\s]*)" on shopping list widget$/
     */
    public function iClickShoppingListOnListsDropdown($name)
    {
        $link = $this->getShoppingListLinkFromShoppingListWidgetByName($name);
        $link->click();
    }

    /**
     * Example: I should see "Shopping List 1" on shopping list widget
     *
     * @Given /^(?:|I )should see "(?P<name>[\w\s\W\S]*)" on shopping list widget$/
     */
    public function iShouldSeeOnShoppingListWidget($name)
    {
        $link = $this->getShoppingListLinkFromShoppingListWidgetByName($name);

        self::assertNotNull($link, sprintf('"%s" list item was not found in shopping list widget', $name));
    }

    /**
     * Example: I should not see "Shopping List 1" on shopping list widget
     *
     * @Given /^(?:|I )should not see "(?P<name>[\w\s\W\S]*)" on shopping list widget$/
     */
    public function iShouldNotSeeOnShoppingListWidget($name)
    {
        $link = $this->getShoppingListLinkFromShoppingListWidgetByName($name);

        self::assertNull($link, sprintf('"%s" list item was found in shopping list widget', $name));
    }

    //@codingStandardsIgnoreStart
    /**
     * @When /^(?:|I )click on "(?P<sku>[^"]+)" configurable product in "(?P<tableName>[^"]+)"(?:| with the following attributes:)$/
     */
    //@codingStandardsIgnoreEnd
    public function iClickOnConfigurableProductWithAttributes(string $sku, string $tableName, TableNode $table = null)
    {
        $attributeLabels = [];
        if ($table) {
            foreach ($table->getRows() as $row) {
                [$attribute, $value] = $row;
                $attributeLabels[] = sprintf('%s: %s', $attribute, $value);
            }
        }

        /** @var ProductTable $shoppingListItemsTableElement */
        $shoppingListItemsTableElement = $this->createValidShoppingListTableElement($tableName);
        /** @var ConfigurableProductTableRowAwareInterface[] $rows */
        $rows = $shoppingListItemsTableElement->getProductRows();

        foreach ($rows as $rowElement) {
            if ($rowElement->getProductSku() !== $sku) {
                continue;
            }

            if ($rowElement->isRowContainingAttributes($attributeLabels)) {
                $rowElement->clickProductLink();

                return;
            }
        }

        self::fail(sprintf(
            'Could not find product with the given "%s" sku and attributes "%s"',
            $sku,
            implode(', ', $attributeLabels)
        ));
    }

    /**
     * @When /^(?:|I )click on "(?P<sku>[^"]+)" product in "(?P<tableName>[^"]+)"$/
     */
    public function iClickOnProductInShoppingList(string $sku, string $tableName)
    {
        /** @var ProductTable $shoppingListItemsTableElement */
        $shoppingListItemsTableElement = $this->createValidShoppingListTableElement($tableName);
        /** @var ProductTableRow[] $rows */
        $rows = $shoppingListItemsTableElement->getProductRows();

        foreach ($rows as $rowElement) {
            if ($rowElement->getProductSku() !== $sku) {
                continue;
            }
            $rowElement->clickProductLink();
        }
    }

    /**
     * @param string $label
     * @return null|ShoppingList
     */
    protected function getShoppingListByLabel($label)
    {
        return $this->getAppContainer()
            ->get('doctrine')
            ->getManagerForClass(ShoppingList::class)
            ->getRepository(ShoppingList::class)
            ->findOneBy(['label' => $label]);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return string
     */
    protected function getShoppingListViewUrl(ShoppingList $shoppingList)
    {
        return $this->getAppContainer()
            ->get('router')
            ->generate('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()]);
    }

    /**
     * @param string $shoppingList
     * @return Table
     */
    private function createValidShoppingListTableElement(string $shoppingList)
    {
        /** @var Table $shoppingListItemsTableElement */
        $shoppingListItemsTableElement = $this->elementFactory->createElement($shoppingList);

        self::assertTrue(
            $shoppingListItemsTableElement->isValid(),
            sprintf('Element "%s" was not found', $shoppingList)
        );

        return $shoppingListItemsTableElement;
    }

    /**
     * @param Table $table
     *
     * @return TableRow[]
     */
    private function getShoppingListLineItemsTableDirectRows(Table $table)
    {
        return array_map(function (NodeElement $element) {
            return $this->elementFactory->wrapElement(Table::TABLE_ROW_STRICT_ELEMENT, $element);
        }, $table->findAll('css', '.shopping-list-line-items .shopping-list-line-items__item-wrapper'));
    }

    /**
     * @param TableRow $tableRowElement
     *
     * @return string
     */
    private function getLineItemQuantity(TableRow $tableRowElement)
    {
        return $tableRowElement->find('css', '.product__qty-input__count-option input')->getValue();
    }

    /**
     * @param TableRow $tableRowElement
     *
     * @return string
     */
    private function getLineItemUnit(TableRow $tableRowElement)
    {
        $select = $tableRowElement->find('css', '.select2-chosen');
        if ($select) {
            return $select->getText();
        } else {
            return $tableRowElement->find('css', '.product__static-unit')->getText();
        }
    }

    /**
     * @param TableRow $tableRowElement
     *
     * @return string
     */
    private function getLineItemSKU(TableRow $tableRowElement)
    {
        return $tableRowElement->find('css', 'span.product-item__sku-value')->getText();
    }

    /**
     * @param TableRow $tableRowElement
     *
     * @return string
     */
    private function getLineItemPrice(TableRow $tableRowElement)
    {
        return $this->createElement('Shopping List Line Item Product Price', $tableRowElement)->getText();
    }

    private function getLineItemRowColumnsValues(TableRow $rowElement, array $columns): array
    {
        $values = [];
        foreach ($columns as $columnTitle) {
            switch (strtolower($columnTitle)) {
                case 'sku':
                    $currentValue = $this->getLineItemSKU($rowElement);
                    break;
                case 'quantity':
                    $currentValue = $this->getLineItemQuantity($rowElement);
                    break;
                case 'unit':
                    $currentValue = $this->getLineItemUnit($rowElement);
                    break;
                case 'price':
                    $currentValue = $this->getLineItemPrice($rowElement);
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf(
                            '%s column is not supported, supported columns is %s',
                            $columnTitle,
                            implode(', ', ['Sku', 'Quantity', 'Unit', 'Price'])
                        )
                    );
                    break;
            }

            $values[$columnTitle] = $currentValue;
        }

        return $values;
    }

    /**
     * @param $name
     * @return null|NodeElement
     */
    protected function getShoppingListLinkFromShoppingListWidgetByName($name)
    {
        $widget = $this->createElement('ShoppingListWidgetContainer');
        $xpath = sprintf(
            '//span[' .
                "@data-role='shopping-list-title'" .
                "and translate(text(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='%s'" .
            ']',
            strtolower($name)
        );

        return $widget->find('xpath', $xpath);
    }
}
