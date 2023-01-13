@regression
@ticket-BB-21014
@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@fixture-OroOrderBundle:SalesOrdersShoppingListsFixture.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroOrderBundle:PaymentTransactionFixture.yml

Feature: Sales Orders on Customer User view page
  In order to know Order History of a Customer User
  As an Administrator
  I want to have a grid with Sales Orders on Customer User view page

  Scenario: Check Customer User view page
    Given I login as administrator
    When I go to Customer / Customer Users
    And I click view "AmandaRCole" in grid
    And I sort "Customer User Sales Orders Grid" by "Order Number"
    Then I should see following "Customer User Sales Orders Grid" grid:
      | Order Number | Payment Method |
      | FirstOrder   | Payment Term   |
      | SecondOrder  |                |
