@regression
@ticket-BB-15845
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:CheckoutProductFixture.yml
@fixture-OroCheckoutBundle:CheckoutShoppingListFixture.yml
@fixture-OroCheckoutBundle:CheckoutQuoteFixture.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@fixture-OroCheckoutBundle:ShippingEur.yml
@fixture-OroCheckoutBundle:PaymentEur.yml

Feature: Re-order using default Checkout workflow with multi currency
  In order to quickly re-order the items I've ordered before
  As a Buyer
  I want to be able to start new checkout using the items from an existing order

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable required currencies
    Given I proceed as the Admin
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create order from Shopping list
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  Scenario: Create order from exist Order
    Given I follow "Account"
    When I click "Order History"
    When I click "Re-Order"
    Then I should be on Checkout page
    Then I should see Checkout Totals with data:
      | Subtotal | $10.00 |
    And Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 5 | items |

  Scenario: Trying to change currency to EUR
    Given I click "Currency Switcher"
    When I click "Euro"
    Then I should see "This checkout can be completed only in US Dollar. Please return to the original page to switch the currency." flash message

  Scenario: Create order from exist Order
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"

  Scenario: Checking Order History grid with Open Orders
    Given I open Order History page on the store frontend
    Then there is no records in "OpenOrdersGrid"
    Then I should see following "Past Orders Grid" grid:
      | Shipping Address                                      | Total  |
      | Primary address ORO Fifth avenue 10115 Berlin Germany | $13.00 |
    And I click "View" on row "1" in grid "PastOrdersGrid"
