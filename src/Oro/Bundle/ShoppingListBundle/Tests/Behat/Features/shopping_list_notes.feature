@regression
@waf-skip
@fixture-OroShoppingListBundle:ShoppingListRule.yml
Feature: Shopping list notes
  As Customer User I have a possibility to save notes to shopping list

  Scenario: Add notes to shopping list
    Given I login as AmandaRCole@example.org buyer
    When Buyer is on "Another List" shopping list
    And I click "Shopping List Actions"
    And I click "Edit"
    And I click "Add a note to entire Shopping List"
    And I type "My shopping list <script>alert('malicious script')</script> notes" in "Shopping List Notes"
    And I click on "Save Shopping List Notes"
    Then I should see "My shopping list <script>alert('malicious script')</script> notes"

  Scenario: Open shopping list as admin
    Given I login as administrator
    And I go to Sales/ Shopping Lists
    When I click view "Another List" in grid
    And I should see "My shopping list <script>alert('malicious script')</script> notes"
