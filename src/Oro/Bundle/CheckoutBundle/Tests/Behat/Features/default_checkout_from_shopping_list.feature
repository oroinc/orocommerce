@ticket-BB-7164
@ticket-BB-16275
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:DefaultCheckoutFromShoppingList.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
@fixture-OroLocaleBundle:GermanLocalization.yml
@community-edition-only

Feature: Default Checkout From Shopping List
  In order to create order on front store
  As a buyer
  I want to start and complete checkout from shopping list

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And I enable the existing localizations

  Scenario: Create order from Shopping List 1 and verify quantity
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I scroll to top
    And I wait line items are initialized
    And I click "Create Order"
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1`"'&йёщ®&reg;> | 5 | items |
    And I should see Checkout Totals with data:
      | Subtotal | $10.00 |
    And I should not see "Back"

    When I open Order History page on the store frontend
    Then I should see following grid:
      | Step                | Started From | Items | Subtotal |
      | Billing Information | List 1       | 1     | $10.00   |
    And I click "Check Out" on row "List 1" in grid "OpenOrdersGrid"

    When I click "Edit items"
    And I wait line items are initialized
    And I type "10" in "Shopping List Line Item 1 Quantity"
    And I should see "Record has been successfully updated" flash message
    And I scroll to top
    And I click "Create Order"
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1`"'&йёщ®&reg;> | 10 | items |
    And I should see Checkout Totals with data:
      | Subtotal | $20.00 |

    When I open Order History page on the store frontend
    Then I should see following grid:
      | Step                | Started From | Items | Subtotal |
      | Billing Information | List 1       | 1     | $20.00   |

  Scenario: Check filter localization
    Given I should see following header in "Filter By Do Not Ship Later Than" filter in "OpenOrdersGrid":
      | S | M | T | W | T | F | S |
    When I click "Localization Switcher"
    And I select "German Localization" localization
    Then I should see following header in "Filter By Do Not Ship Later Than" filter in "OpenOrdersGrid":
      | M | D | M | D | F | S | S |
    And I click "Localization Switcher"
    And I select "English" localization
    And I click "Check Out" on row "List 1" in grid "OpenOrdersGrid"

  Scenario: Process checkout
    When I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1`"'&йёщ®&reg;> | 10 | items |
    When I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1`"'&йёщ®&reg;> | 10 | items |
    When I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1`"'&йёщ®&reg;> | 10 | items |
    When I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1`"'&йёщ®&reg;> | 10 | items |
    And I should see following header in "Do not ship later than Datepicker":
      | S | M | T | W | T | F | S |
    And I click "Localization Switcher"
    And I select "German Localization" localization
    And I should see following header in "Do not ship later than Datepicker":
      | M | D | M | D | F | S | S |
    And I click "Localization Switcher"
    And I select "English" localization
    When I check "Delete this shopping list after submitting order" on the "Order Review" checkout step and press Submit Order
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

    When I follow "click here to review"
    Then I should be on Order Frontend View page
    And I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"

  Scenario: Checking Order History grid with Open Orders
    Given I open Order History page on the store frontend
    When there is no records in "OpenOrdersGrid"
    And I click "View" on row "1" in grid "PastOrdersGrid"
    Then I should be on Order Frontend View page
    And I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"
