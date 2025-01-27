@ticket-BB-23561
@regression
@fixture-OroCheckoutBundle:ReOrder/BaseIntegrationsFixture.yml
@fixture-OroCheckoutBundle:AdditionalIntegrations.yml
@fixture-OroCheckoutBundle:ReOrder/CustomerUserFixture.yml
@fixture-OroCheckoutBundle:ReOrder/CustomerUserAddressFixture.yml
@fixture-OroCheckoutBundle:ReOrder/ProductFixture.yml
@fixture-OroCheckoutBundle:ReOrder/OrderFixture.yml
@fixture-OroCheckoutBundle:ReOrder/PaymentTransactionFixture.yml

Feature: Minimum and maximum order amounts on re-order
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
    And I fill in "Minimum Order Amount USD Config Field" with "35"
    And uncheck "Use default" for "Maximum Order Amount" field
    And I fill in "Maximum Order Amount USD Config Field" with "2000"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check it's not possible to start order from re-order in case subtotal is less than minimum order amount setting
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click "Account Dropdown"
    And I click "Order History"
    And I click "Re-Order" on row "SecondOrder" in grid "Past Orders Grid"
    Then I should be on Order History page
    And I should see "A minimum order subtotal of $35.00 is required to check out. Please add $15.00 more to proceed." flash message

  Scenario: Check it's not possible to start order from re-order in case subtotal is more than maximum order amount setting
    When I click "Account Dropdown"
    And I click "Order History"
    And I click "Re-Order" on row "FirstOrder" in grid "Past Orders Grid"
    Then I should be on Order History page
    # Order limit validator validates order subtotal of $3,585.00, not the total of $2,121.00
    # which is visible on this grid and which includes discounts
    And I should see "The order subtotal cannot exceed $2,000.00. Please remove at least $1,585.00 to proceed." flash message
