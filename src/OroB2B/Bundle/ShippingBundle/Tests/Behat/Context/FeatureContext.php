<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Behat\Context;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext
{

    /**
     * @Given Admin User has Shipping Rulesт Full permissions
     */
    public function adminUserHasShippingRulesFullPermissions()
    {
        throw new PendingException();
    }

    /**
     * @Given Buyer User with Edit Shipping Address permissions
     */
    public function buyerUserWithEditShippingAddressPermissions()
    {
        throw new PendingException();
    }

    /**
     * @Given Shopping Rule Flat Rate Shipping Cost = :arg1
     */
    public function shoppingRuleFlatRateShippingCost($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given Shopping Rule Flat Rate Type = per Order by default
     */
    public function shoppingRuleFlatRateTypePerOrderByDefault()
    {
        throw new PendingException();
    }

    /**
     * @Given Shopping Rule Flat Rate Handling Fee = :arg1
     */
    public function shoppingRuleFlatRateHandlingFee($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given Admin User created Flat Rate Shipping Rule #:arg1 with next data:
     */
    public function adminUserCreatedFlatRateShippingRuleWithNextData(TableNode $table)
  {
      throw new PendingException();
  }

    /**
     * @Given Buyer created order with:
     */
    public function buyerCreatedOrderWith(TableNode $table)
    {
        throw new PendingException();
    }

    /**
     * @When Buyer is on Shipping Method Checkout step
     */
    public function buyerIsOnShippingMethodCheckoutStep()
    {
        throw new PendingException();
    }

    /**
     * @Then Shipping Type Flat Rate :arg1 EUR is shown for Buyer selection
     */
    public function shippingTypeFlatRateEurIsShownForBuyerSelection($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then One the next Checkout step order subtotal is recalculated to :arg1
     */
    public function oneTheNextCheckoutStepOrderSubtotalIsRecalculatedTo($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given Admin User edited :arg1 with next data:
     */
    public function adminUserEditedWithNextData($arg1, TableNode $table)
    {
        throw new PendingException();
    }

    /**
     * @Then Flat Rate is non-visible for Buyer selection
     */
    public function flatRateIsNonVisibleForBuyerSelection()
    {
        throw new PendingException();
    }

    /**
     * @Given the product unit :arg1 was uploaded in database
     */
    public function test($arg1)
    {
        sleep(30);
    }
}
