@fixture-OroShoppingListBundle:ShoppingListFixture.yml
Feature: Shopping list title
  As Customer User I have a possibility to edit title at shopping list
  Use inline edit form

  Scenario: Shopping list label validation
    Given I login as AmandaRCole@example.org buyer
    And Buyer is on "Shopping List 1" shopping list
    When I click "Shopping List Actions"
    And I click "Rename"
    And I fill "Shopping List Rename Action Form" with:
      | Label | Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. |
    And I click "Shopping List Action Submit"
    Then I should see "This value is too long. It should have 255 characters or less."
    When I fill "Shopping List Rename Action Form" with:
      | Label | |
    And I click "Shopping List Action Submit"
    Then I should see "This value should not be blank."
    When I fill "Shopping List Rename Action Form" with:
      | Label | Shopping List 2 |
    And I click "Shopping List Action Submit"
    Then I should see "Shopping list has been successfully renamed" flash message and I close it
    And I should see "Signed in as: Amanda Cole"
