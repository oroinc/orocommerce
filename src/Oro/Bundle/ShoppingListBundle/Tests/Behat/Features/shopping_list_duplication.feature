@fixture-OroShoppingListBundle:DuplicateShoppingListFixture.yml
Feature: Shopping list duplication
  In order to manager shopping lists on front store
  As a Buyer
  I need to be able to clone shopping list

  Scenario: Feature Background
    Given I login as AmandaRCole@example.org buyer

  Scenario: Duplicate shopping list
    When Buyer is on "Shopping List A" shopping list
    And I click "Duplicate List"
    Then I should see "The shopping list has been duplicated" flash message
    And I should not see "Some products are not available and cannot be added to shopping list" flash message
    And I should see "Shopping List A (copied "
    And I should see following line items in "Shopping List Line Items Table":
      | SKU | Quantity | Unit |
      | AA1 | 10       | item |
      | AA3 | 20       | item |

  Scenario: Duplicate shopping list with restricted items
    When Buyer is on "Shopping List B" shopping list
    And I click "Duplicate List"
    Then I should see "The shopping list has been duplicated" flash message
    And I should see "Some products are not available and cannot be added to shopping list" flash message
    And I should see "Shopping List B (copied "
    And I should see following line items in "Shopping List Line Items Table":
      | SKU | Quantity | Unit |
      | AA3 | 40       | item |
    And I should not see "AA2"

  Scenario: Check duplicate button for shopping list with all restricted items
    When Buyer is on "Shopping List C" shopping list
    Then I should not see "Duplicate List"
