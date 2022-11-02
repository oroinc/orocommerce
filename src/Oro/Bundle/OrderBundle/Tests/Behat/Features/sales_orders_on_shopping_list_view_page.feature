@ticket-BB-16077
@ticket-BB-21014
@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@fixture-OroOrderBundle:SalesOrdersShoppingListsFixture.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroOrderBundle:PaymentTransactionFixture.yml

Feature: Sales Orders on Shopping List view page
  In order to understand if the shopping list has been converted into an order
  As an Administrator
  I want to have a grid with related Orders on Shopping List page

  Scenario: Check Order view page
    Given I login as administrator
    And I go to Sales / Orders
    And I should see following grid:
      | Order Number | Payment Method |
      | FirstOrder   | Payment Term   |
      | SecondOrder  |                |
    And I filter "PO Number" as contains "ORD1"
    When I click view ORD1 in grid
    Then I should see 'Shopping List "Shopping list"' link with the url matches "shoppinglist/view"

  Scenario: Check Shopping List view page
    When I click 'Shopping List "Shopping list"'
    And I sort "Shopping list Orders Grid" by "Order Number"
    Then I should see Shopping List with:
      | Customer      | first customer  |
      | Customer User | Amanda Cole     |
      | Label         | Shopping List 1 |
    And I should see following "Shopping list Orders Grid" grid:
      | Order Number | Payment Term | Currency | Total   | Total ($) | # Line Items | Internal Status | Payment Status | Payment Method | Shipping Method | Special Discounts | Customer       | Customer User |
      | FirstOrder   | net 10       | USD      | $51.00  | $51.00    | 1            | Open            |                | Payment Term   |                 | $0.00             | first customer | Amanda Cole   |
      | SecondOrder  | net 10       | USD      | $102.00 | $102.00   | 1            | Open            |                |                |                 | $0.00             | first customer | Amanda Cole   |
