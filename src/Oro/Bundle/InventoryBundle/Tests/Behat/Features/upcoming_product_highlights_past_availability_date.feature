@ticket-BB-16265
@ticket-BB-16591
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroInventoryBundle:checkout.yml

Feature: Upcoming product highlights past availability date
  In order to inform customers about future availability of a product
  As a Merchant
  I want upcoming products with past availability date reset their Upcoming status

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Check that "Hide Labels Past Availability Date" configuration option is true
    Given I login as administrator
    And I go to System/ Configuration
    When I follow "Commerce/Inventory/Product Options" on configuration sidebar
    Then "OroForm" must contains values:
      | Hide Labels Past Availability Date | true |

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
    And expand "NewCategory" in tree
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
      | Availability date | <DateTime:Feb 1, 2020 12:00 PM> |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "Upcoming No"
    And I should not see "Availability Date"

  Scenario: Check that 'Upcoming' details correctly displayed on frontend product pages:
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I scroll to text "NewCategory2"
    And I click on "NewCategory2 category"
    Then I should see "This product will be available on 12/1/2040" for "SKU2" product
    Then I should not see "This product will be available later" for "SKU3" product
    When I click "View Details" for "SKU2" product
    Then I should see "This product will be available on 12/1/2040"
    And click on "NewCategory2 breadcrumb item"
    When I click "View Details" for "SKU3" product
    Then I should not see "This product will be available on 2/1/2020"

  Scenario: Check that product`s availability date correctly handled during checkout process
    Given I open page with shopping list List 1
    Then I should see notification "This product will be available on 1/1/2030" for "SKU1" line item "ShoppingListLineItem"
    And I should see notification "This product will be available on 12/1/2040" for "SKU2" line item "ShoppingListLineItem"
    When I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see notification "This product will be available on 1/1/2030" for "SKU1" line item "ProductLineItem"
    And I should see notification "This product will be available on 12/1/2040" for "SKU2" line item "ProductLineItem"
    When I click on "Do not ship later than Datepicker"
    Then I should see "12/1/2040"
    When I fill "Checkout Order Review Form" with:
      | Do not ship later than | 7/1/2018 |
    And I click "Submit Order"
    Then I should see "There was an error while processing the order"
    When I fill "Checkout Order Review Form" with:
      | PO Number              | PONumber 121 |
      | Do not ship later than | 12/1/2040 |
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase"
    And should see "Your order number is 1"

  Scenario: Check that upcoming products with unknown availability date is correctly handled during checkout process
    Given I open page with shopping list List 2
    When I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see notification "This product will be available on 1/1/2030" for "SKU1" line item "ProductLineItem"
    And I should not see notification "This product will be available later" for "SKU3" line item "ProductLineItem"
    And I should not see "This order contains upcoming products without availability date"
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase"

  Scenario: Check that upcoming products with availability date in the past is correctly handled during checkout process
    Given I open page with shopping list List 3
    When I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    When I click on "Do not ship later than Datepicker"
    And I fill "Checkout Order Review Form" with:
      | Do not ship later than | today |
    And I click "Submit Order"
    Then I should see "Thank You For Your Purchase"

