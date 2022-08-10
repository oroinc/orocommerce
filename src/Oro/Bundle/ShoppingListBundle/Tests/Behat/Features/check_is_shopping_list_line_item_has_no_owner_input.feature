@regression
@ticket-BB-10495
@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@fixture-OroShoppingListBundle:ProductFixture.yml

Feature: Check is shopping list line item has no owner input
  As an Admin
  I don`t need to be able to add or edit owner on shopping list line items

  Scenario: Add Shopping List Line Item
    Given I login as administrator
    When I go to Sales/ Shopping Lists
    And I click View Shopping List 5 in grid
    And I click "Add Line Item"
    Then I should see "UiDialog" with elements:
      | Title        | Add Line Item |
      | okButton     | Save          |
      | cancelButton | Cancel        |
    And I should not see an "Shopping List Line Item Owner Form Field" element

    When I fill form with:
      | Product  | PSKU1     |
      | Quantity | 1         |
      | Unit     | each      |
      | Notes    | autoowner |
    And I click "Save" in modal window
    Then I should see "Line item has been added" flash message

  Scenario: Owner field is absent on "Edit Shopping List Line Item" form
    When I click Edit AA1 in grid
    Then I should see "UiDialog" with elements:
      | Title        | Edit Line Item |
      | okButton     | Save           |
      | cancelButton | Cancel         |
    And I should not see an "Shopping List Line Item Owner Form Field" element
    And I click "Cancel"

  Scenario: Create Shopping List Line Item report
    When I go to Reports & Segments/ Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Shopping List Line Item report |
      | Entity      | Shopping List Line Item        |
      | Report Type | Table                          |
    And I add the following columns:
      | Shopping List->Label | None | Shopping List Label |
      | Shopping List->Owner | None | Shopping List Owner |
      | Owner                | None | Owner               |
    And I add the following filters:
      | Field Condition | Notes | is equal to | autoowner |
    And I save and close form
    Then I should see "Report saved" flash message
    And I should see following grid:
      | Shopping List Label | Shopping List Owner | Owner    |
      | Shopping List 5     | John Doe            | John Doe |
