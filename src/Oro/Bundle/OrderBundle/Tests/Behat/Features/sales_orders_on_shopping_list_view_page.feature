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
    And I show column Payment Method in grid
    And I should see following grid:
      | Order Number | Payment Method |
      | SecondOrder  |                |
      | FirstOrder   | Payment Term   |
    When I click view FirstOrder in grid
    Then I should see 'Shopping List "Shopping list"' link with the url matches "shoppinglist/view"

  Scenario: Check Shopping List view page
    When I click 'Shopping List "Shopping list"'
    And I sort "Shopping list Orders Grid" by "Order Number"
    Then I should see Shopping List with:
      | Customer      | first customer  |
      | Customer User | Amanda Cole     |
      | Label         | Shopping List 1 |
    And I show column Payment Method in "Shopping list Orders Grid"
    And I should see following "Shopping list Orders Grid" grid:
      | Order Number | Total   | Total ($) | # Line Items | Internal Status | Payment Status | Payment Method | Customer       | Customer User |
      | FirstOrder   | $51.00  | $51.00    | 1            | Open            | Paid in full   | Payment Term   | first customer | Amanda Cole   |
      | SecondOrder  | $102.00 | $102.00   | 1            | Open            |                |                | first customer | Amanda Cole   |
