@ticket-BB-23561
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Minimum and maximum order amounts on quick order form
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

  Scenario: Check it's not possible to start order from quick order form in case subtotal is less than minimum order amount setting
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | SKU123 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1  | 5     |
    When I click "Create Order"
    Then I should be on Quick Order page
    And I should see "A minimum order subtotal of $11.00 is required to check out. Please add $1.00 more to proceed." flash message

  Scenario: Check temporary shopping list was not created while checking for minimum order amount
    When I click "Account Dropdown"
    And I click on "Shopping Lists"
    Then records in grid should be 2

  Scenario: Check it's possible to start order from quick order form when minimum order amount is met
    When I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | SKU123 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1  | 6     |
    And I click "Create Order"
    Then I should be on Checkout page
    And I click "Ship to This Address"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Check it's not possible to start order from quick order form in case subtotal is more than maximum order amount setting
    When I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | SKU123 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1  | 15    |
    And I click "Create Order"
    Then I should be on Quick Order page
    And I should see "The order subtotal cannot exceed $19.00. Please remove at least $11.00 to proceed." flash message

  Scenario: Check temporary shopping list was not created while checking for maximum order amount
    When I click "Account Dropdown"
    And I click on "Shopping Lists"
    Then records in grid should be 2

  Scenario: Check it's possible to start order from quick order form when maximum order amount is met
    When I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | SKU123 |
    And I wait for products to load
    And I fill "Quick Order Form" with:
      | QTY1  | 9     |
    And I click "Create Order"
    Then I should be on Checkout page
    And I click "Ship to This Address"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
