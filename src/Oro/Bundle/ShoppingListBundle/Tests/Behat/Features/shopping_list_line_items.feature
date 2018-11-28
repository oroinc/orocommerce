@fixture-OroShoppingListBundle:ShoppingListFixture.yml
Feature: Shopping List Line Items
  In order to manager shopping lists on front store
  As a Buyer
  I need to be able to update shopping list

  Scenario: Merge Line items
    Given I login as AmandaRCole@example.org buyer
    And Buyer is on Shopping List 5
    And I should see following line items in "Shopping List Line Items Table":
      | SKU | Quantity | Unit |
      | AA1 | 1        | set  |
      | AA1 | 2        | item |
    When I fill "Shopping List Line Item 1 Form" with:
      | Unit | item |
    Then I should see following line items in "Shopping List Line Items Table":
      | SKU | Quantity | Unit |
      | AA1 | 3        | item |
