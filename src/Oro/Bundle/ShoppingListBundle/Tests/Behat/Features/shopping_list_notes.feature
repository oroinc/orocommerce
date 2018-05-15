@regression
@fixture-OroShoppingListBundle:ShoppingListRule.yml
Feature: Shopping list notes
  As Customer User I have a possibility to save notes to shopping list

  Scenario: Add notes to shopping list
    Given I login as AmandaRCole@example.org buyer
    When Buyer is on Another List
    And I click "View Options for this Shopping List"
    And I click on "Add a Note to This Shopping List"
    And I type "My shopping list <script>alert('malicious script')</script> notes" in "shopping_list_notes"
    And I click on empty space
    And I should see "Record has been successfully updated" flash message

  Scenario: Open shopping list as admin
    Given I login as administrator
    And I go to Sales/ Shopping Lists
    When I click view "Another List" in grid
    And I should see "My shopping list <script>alert('malicious script')</script> notes"
