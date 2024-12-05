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
    When I click "Shopping List Actions"
    And I click "Add Note"
    And I type "My shopping list <script>alert('malicious script')</script> notes" in "Shopping List Notes in Modal"
    And I press "Space" key on "UiWindow okButton" element
    Then I should see "My shopping list <script>alert('malicious script')</script> notes"

  Scenario: Open shopping list as admin
    Given I login as administrator
    And I go to Sales/ Shopping Lists
    When I click view "Another List" in grid
    And I should see "My shopping list <script>alert('malicious script')</script> notes"
