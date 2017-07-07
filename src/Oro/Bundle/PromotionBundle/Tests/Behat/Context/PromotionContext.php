<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\DiscountSubtotalAwareInterface;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionCheckoutStep;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionOrder;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionShoppingList;
use Oro\Bundle\PromotionBundle\Tests\Behat\Element\PromotionShoppingListLineItem;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemsAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class PromotionContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @Then /^(?:|I )see next line item discounts for shopping list "(?P<shoppingListLabel>[^"]+)":$/
     *
     * @param string $shoppingListLabel
     * @param TableNode $table
     */
    public function assertShoppingListLineItemDiscount($shoppingListLabel, TableNode $table)
    {
        /** @var PromotionShoppingList $shoppingList */
        $shoppingList = $this->createElement('PromotionShoppingList');

        $shoppingList->assertTitle($shoppingListLabel);

        $this->assertLineItemDiscounts($shoppingList, $table);
    }

    /**
     * @Then /^(?:|I )see next line item discounts for checkout:$/
     *
     * @param TableNode $table
     */
    public function assertCheckoutStepLineItemDiscount(TableNode $table)
    {
        /** @var PromotionCheckoutStep $checkoutStep */
        $checkoutStep = $this->createElement('PromotionCheckoutStep');

        $this->assertLineItemDiscounts($checkoutStep, $table);
    }

    /**
     * @Then /^(?:|I )see next line item discounts for order:$/
     *
     * @param TableNode $table
     */
    public function assertOrderLineItemDiscount(TableNode $table)
    {
        /** @var PromotionOrder $order */
        $order = $this->createElement('PromotionOrder');

        $this->assertLineItemDiscounts($order, $table);
    }

    // @codingStandardsIgnoreStart
    /**
     * @Then /^(?:|I )see "(?P<subtotalDiscount>[^"]+)" subtotal discount for shopping list "(?P<shoppingListLabel>[^"]+)"$/
     *
     * @param string $shoppingListLabel
     * @param string $subtotalDiscount
     */
    // @codingStandardsIgnoreEnd
    public function assertShoppingListSubtotalDiscount($shoppingListLabel, $subtotalDiscount)
    {
        /** @var PromotionShoppingList $shoppingList */
        $shoppingList = $this->createElement('PromotionShoppingList');
        $shoppingList->assertTitle($shoppingListLabel);

        $this->assertDiscountSubtotal($shoppingList, $subtotalDiscount);
    }

    /**
     * @Then /^(?:|I )see "(?P<subtotalDiscount>[^"]+)" subtotal discount for checkout step$/
     *
     * @param string $subtotalDiscount
     */
    public function assertCheckoutStepSubtotalDiscount($subtotalDiscount)
    {
        /** @var PromotionCheckoutStep $checkoutStep */
        $checkoutStep = $this->createElement('PromotionCheckoutStep');

        $this->assertDiscountSubtotal($checkoutStep, $subtotalDiscount);
    }

    /**
     * @Then /^(?:|I )see "(?P<subtotalDiscount>[^"]+)" subtotal discount for order$/
     *
     * @param string $subtotalDiscount
     */
    public function assertOrderSubtotalDiscount($subtotalDiscount)
    {
        /** @var PromotionOrder $order */
        $order = $this->createElement('PromotionOrder');

        $this->assertDiscountSubtotal($order, $subtotalDiscount);
    }

    /**
     * @param LineItemsAwareInterface $element
     * @param $table
     */
    private function assertLineItemDiscounts($element, TableNode $table)
    {
        $discounts = [];
        /** @var PromotionShoppingListLineItem $lineItem */
        foreach ($element->getLineItems() as $lineItem) {
            $discounts[] = [$lineItem->getProductSKU(), $lineItem->getDiscount()];
        }

        $rows = $table->getRows();
        array_shift($rows);

        static::assertEquals($rows, $discounts);
    }

    /**
     * @param DiscountSubtotalAwareInterface $element
     * @param string $subtotalDiscount
     */
    private function assertDiscountSubtotal(DiscountSubtotalAwareInterface $element, $subtotalDiscount)
    {
        static::assertEquals($subtotalDiscount, $element->getDiscountSubtotal());
    }
}
