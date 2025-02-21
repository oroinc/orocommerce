@ticket-BB-23561
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Minimum and maximum order amounts on the shopping list
  In order to prevent small and unprofitable for the merchant orders from getting into the system
  As an administrator
  I want to have the ability to set Minimum Order Amounts to set various minimum monetary thresholds

  Scenario: Feature Background
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |

  Scenario: Set minimum and maximum order amounts in the system config
    Given I proceed as the Admin
    And login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Minimum Order Amount" field
    And I fill in "Minimum Order Amount USD Config Field" with "11"
    And uncheck "Use default" for "Maximum Order Amount" field
    And I fill in "Maximum Order Amount USD Config Field" with "19"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check it's not possible to start order from shopping list in case subtotal is less than minimum order amount setting
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    Then I should see "A minimum order subtotal of $11.00 is required to check out. Please add $1.00 more to proceed."
    And I should see a "Disabled Create Order From Shopping List Button" element
    And I should not see a "Create Order From Shopping List Button" element
    When I click on "Shopping List Line Item 1 Quantity"
    And I type "6" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    Then I should not see "A minimum order subtotal of $11.00 is required to check out"
    And I should see a "Create Order From Shopping List Button" element
    And I should not see a "Disabled Create Order From Shopping List Button" element
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Order limits are not validated when editing order in back office
    Given I proceed as the Admin
    When I go to Sales/Orders
    And I click Edit "$15.00" in grid
    And fill "Order Form" with:
      | Quantity | 5 |
    And I save and close form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Check it's not possible to start order from shopping list in case subtotal is more than maximum order amount setting
    Given I proceed as the Buyer
    When I open page with shopping list List 2
    Then I should see "The order subtotal cannot exceed $19.00. Please remove at least $1.00 to proceed."
    And I should see a "Disabled Create Order From Shopping List Button" element
    And I should not see a "Create Order From Shopping List Button" element
    When I click on "Shopping List Line Item 1 Quantity"
    And I type "9" in "Shopping List Line Item 1 Quantity Input"
    And I click on "Shopping List Line Item 1 Save Changes Button"
    Then I should not see "The order subtotal cannot exceed $19.00"
    And I should see a "Create Order From Shopping List Button" element
    And I should not see a "Disabled Create Order From Shopping List Button" element
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
