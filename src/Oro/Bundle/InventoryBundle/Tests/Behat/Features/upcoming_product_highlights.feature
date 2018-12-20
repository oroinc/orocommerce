@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroInventoryBundle:checkout.yml

Feature: Upcoming product highlights
  In order to inform customers about future availability of a product
  As a Merchant
  I want to designate some products or product categories as Upcoming

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Set 'Upcoming' flag to product
    Given I proceed as the Admin
    And I login as administrator
    And I disable inventory management
    When I go to Products/Products
    And click edit "SKU1" in grid
    Then I should see "Is Upcoming"
    And I should not see "Availability Date"
    And fill "Products Product Option Form" with:
      | Is Upcoming Use   | false                           |
      | Is Upcoming       | 1                               |
      | Availability date | <DateTime:Jan 1, 2030 12:00 PM> |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "Upcoming Yes"
    And I should see "Availability Date Jan 1, 2030, 12:00 PM"

  Scenario: Set 'Upcoming' flag to category
    When I go to Products/Products
    And click edit "SKU2" in grid
    And fill "Products Product Option Form" with:
      | Is Upcoming Use         | true              |
      | Is Upcoming Fallback To | Category Defaults |
    And I should not see "Availability Date"
    When I save and close form
    Then I should see "Product has been saved" flash message
    Then I should see "Upcoming No"
    And I should not see "Availability Date"
    When I go to Products/ Master Catalog
    And I click "NewCategory2"
    Then I should see "Is Upcoming"
    And I should not see "Availability Date"
    And fill "Category Product Option Form" with:
      | Is Upcoming Use   | false                           |
      | Is Upcoming       | 1                               |
      | Availability date | <DateTime:Dec 1, 2040 04:00 PM> |
    And I click "Save"
    Then I should see "Category has been saved" flash message
    When I go to Products/Products
    And click view "SKU2" in grid
    Then I should see "Upcoming Yes"
    And I should see "Availability Date Dec 1, 2040, 4:00 PM"

  Scenario: Set 'Upcoming' flag to product with date from past
    When I go to Products/Products
    And click edit "SKU3" in grid
    And fill "Products Product Option Form" with:
      | Is Upcoming Use   | false                |
      | Is Upcoming       | 1                    |
      | Availability date | <DateTime:Feb 1, 2010 12:00 PM> |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "Upcoming Yes"
    And I should see "Availability Date N/A"

  Scenario: Check that 'Upcoming' details correctly displayed on frontend product pages:
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I scroll to text "NewCategory2"
    And click "NewCategory2"
    Then I should see "This product will be available on Dec 1, 2040" for "SKU2" product
    Then I should see "This product will be available later" for "SKU3" product
    When I click "View Details" for "SKU2" product
    Then I should see "This product will be available on Dec 1, 2040"

  Scenario: Check that product`s availability date correctly handled during checkout process
    Given I open page with shopping list List 1
    Then I should see "This product will be available on Jan 1, 2030" for "SKU1" line item "ShoppingListLineItem"
    And I should see "This product will be available on Dec 1, 2040" for "SKU2" line item "ShoppingListLineItem"
    When I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see "This product will be available on Jan 1, 2030" for "SKU1" line item "ProductLineItem"
    And I should see "This product will be available on Dec 1, 2040" for "SKU2" line item "ProductLineItem"
    When I click on "Do not ship later than Datepicker"
    Then I should see "December 2040"
    When I fill "Checkout Order Review Form" with:
      | Do not ship later than | Jul 1, 2018 |
    And I click "Submit Order"
    Then I should see "There was an error while processing the order"
    When I fill "Checkout Order Review Form" with:
      | Do not ship later than | Dec 1, 2040 |
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase"

  Scenario: Check that upcoming products with unknown availability date is correctly handled during checkout process
    Given I open page with shopping list List 2
    When I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see "This product will be available on Jan 1, 2030" for "SKU1" line item "ProductLineItem"
    And I should see "This product will be available later" for "SKU3" line item "ProductLineItem"
    And I should see "This order contains upcoming products without availability date"
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase"
