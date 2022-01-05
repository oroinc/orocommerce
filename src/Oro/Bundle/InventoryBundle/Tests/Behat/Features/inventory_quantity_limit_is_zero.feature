@ticket-BB-20933
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Inventory quantity limit is zero
  In order to set Maximum Quantity To Order to 0
  As a buyer
  I want to be able to see correct translation for product in Shopping List

  Scenario: Feature Background
    Given sessions active:
    | Admin | first_session  |
    | Buyer | second_session |

  Scenario: Set Maximum Quantity To Order to 0
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/Products
    And click edit SKU123 in grid
    And I click "Inventory" in scrollspy
    And I fill "Products Product Option Form" with:
      | Maximum Quantity To Order Use | false |
      | Maximum Quantity To Order     | 0     |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check translation of validation message in Shopping List
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I scroll to top
    When I hover on "Shopping List Widget"
    And I click "View Shopping List Details"
    Then I should see notification "The item SKU123: 400-Watt Bulb Work Light is not available to order." for "SKU123" product with Unit of Quantity "item" in shopping list
