<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\RFPBundle\Tests\Behat\Element\RequestForQuote;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ShoppingList as ShoppingListElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * The context for testing Shopping List related features.
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @When /^Buyer is on (?P<shoppingListLabel>[\w\s]+)$/
     *
     * @param string $shoppingListLabel
     */
    public function buyerIsOnShoppingList($shoppingListLabel)
    {
        $shoppingList = $this->getShoppingListByLabel($shoppingListLabel);
        $this->visitPath($this->getShoppingListViewUrl($shoppingList));
        $this->waitForAjax();

        /* @var $element ShoppingListElement */
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

        /* @var $page RequestForQuote */
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
        /* @var $page RequestForQuote */
        $page = $this->createElement('RequestForQuote');
        $page->assertTitle('Request For Quote');

        /* @var $element Element */
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

        $rows = $this->getShoppingListLineItemsTableDirectRows($shoppingListItemsTableElement);
        /** @var TableRow $row */
        $row = $rows[$itemPosition - 1];
        $button = $row->find('css', 'button');

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
        $shoppingListItemsTableElement = $this->elementFactory->createElement($shoppingList);

        $rows = $this->getShoppingListLineItemsTableDirectRows($shoppingListItemsTableElement);

        foreach ($rows as $rowElement) {
            self::assertTrue(
                $this->currentLineItemRowAppearsInExpectedLineItems($rowElement, $table),
                vsprintf(
                    'Row "%s, %s, %s" isn\'t expected',
                    [
                        $this->getLineItemSKU($rowElement),
                        $this->getLineItemUnit($rowElement),
                        $this->getLineItemQuantity($rowElement),
                    ]
                )
            );
        }
    }

    /**
     * @When /^(?:|I )should see following header in shopping list line items table:$/
     *
     * @param TableNode $table
     */
    public function iShouldSeeFollowingColumns(TableNode $table)
    {
        $rows = $table->getRows();
        self::assertNotEmpty($rows);

        /* @var $element ShoppingListElement */
        $element = $this->createElement('ShoppingList');

        self::assertEquals(reset($rows), $element->getLineItemsHeader());
    }

    /**
     * @param string $label
     * @return null|ShoppingList
     */
    protected function getShoppingListByLabel($label)
    {
        return $this->getContainer()
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
        return $this->getContainer()
            ->get('router')
            ->generate('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()]);
    }

    /**
     * @param Table $table
     *
     * @return TableRow[]
     */
    private function getShoppingListLineItemsTableDirectRows(Table $table)
    {
        return array_map(function (NodeElement $element) {
            return $this->elementFactory->wrapElement(Table::TABLE_ROW_ELEMENT, $element);
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
     * @param TableRow  $rowElement
     * @param TableNode $expectedLineItemsTable
     *
     * @return bool
     */
    private function currentLineItemRowAppearsInExpectedLineItems(
        TableRow $rowElement,
        TableNode $expectedLineItemsTable
    ) {
        $sku = $this->getLineItemSKU($rowElement);
        $quantity = $this->getLineItemQuantity($rowElement);
        $unit = $this->getLineItemUnit($rowElement);
        $allElementAppear = false;
        foreach ($expectedLineItemsTable as $index => $row) {
            $skuAppear = false;
            $quantityAppear = false;
            $unitAppear = false;
            foreach ($row as $columnTitle => $value) {
                switch (strtolower($columnTitle)) {
                    case 'sku':
                        $skuAppear = $value === $sku;
                        break;
                    case 'quantity':
                        $quantityAppear = $value === $quantity;
                        break;
                    case 'unit':
                        $unitAppear = $value === $unit;
                        break;
                    default:
                        throw new \InvalidArgumentException(
                            sprintf(
                                '%s column is not supported, supported columns is %s',
                                $columnTitle,
                                implode(', ', ['Sku', 'Quantity', 'Unit'])
                            )
                        );
                        break;
                }
            }
            if ($quantityAppear && $unitAppear && $skuAppear) {
                $allElementAppear = true;
            }
        }

        return $allElementAppear;
    }

    /**
     * @Then I open shopping list widget
     */
    public function iOpenShoppingListWidget()
    {
        $this->createElement('ShoppingListWidget')->click();
    }
}
