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

Feature: Default Checkout From Shopping List With Multi Currency
  In order to create order on front store
  As a buyer
  I want to start and complete checkout from shopping list with few currencies

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable required currencies
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Start checkout from Shopping List 1 with USD currency
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    When I click "Create Order"
    Then I should see Checkout Totals with data:
      | Subtotal | $10.00 |
    And Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 5 | items |

  Scenario: Trying to change currency to EUR
    Given I click "Currency Switcher"
    When I click "Euro"
    Then I should see "This checkout can be completed only in US Dollar. Please return to the original page to switch the currency." flash message

  Scenario: Check checkout grid
    When I open Order History page on the store frontend
    Then I should see following grid:
      | Step                | Started From | Currency | Items | Subtotal |
      | Billing Information | List 1       | USD      | 1     | $10.00   |

  Scenario: Start checkout from Shopping List 1 with EUR currency
    Given I open page with shopping list List 1
    And I click "Currency Switcher"
    When I click "Euro"
    When I click "Create Order"
    Then I should see Checkout Totals with data:
      | Subtotal | €9.50 |
    And Checkout "Order Summary Products Grid" should contain products:
      | 400-Watt Bulb Work Light | 5 | items |

  Scenario: Trying to change currency to USD
    Given I click "Currency Switcher"
    When I click "US Dollar"
    Then I should see "This checkout can be completed only in Euro. Please return to the original page to switch the currency." flash message

  Scenario: Check checkout grid
    When I open Order History page on the store frontend
    Then I should see following grid:
      | Step                | Started From | Currency | Items | Subtotal |
      | Billing Information | List 1       | USD      | 1     | €9.50    |
      | Billing Information | List 1       | EUR      | 1     | €9.50    |

  Scenario: Process checkout
    Given I open page with shopping list List 1
    When I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    And I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I follow "click here to review"
    Then I should be on Order Frontend View page

  Scenario: Checking Order History grid with Open Orders
    Given I open Order History page on the store frontend
    Then there is no records in "OpenOrdersGrid"
    Then I should see following "Past Orders Grid" grid:
      | Shipping Address                                      | Total  |
      | Primary address ORO Fifth avenue 10115 Berlin Germany | €12.30 |
    And I click "View" on row "1" in grid "PastOrdersGrid"
