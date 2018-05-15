@fixture-OroShoppingListBundle:ShoppingListFixture.yml
Feature: Shopping list title
  As Customer User I have a possibility to edit title at shopping list
  Use inline edit form

  Scenario: Shopping list label validation
    Given I login as AmandaRCole@example.org buyer
    And Buyer is on Shopping List 5
    When I click "Edit Shoppping List Label"
    And I type "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum." in "Shopping List Edit Title Field"
    And I click "Save"
    Then I should see "This value is too long. It should have 255 characters or less."
    And I should see "Signed in as: Amanda Cole"
    Then I type "" in "Shopping List Edit Title Field"
    And I click "Save"
    And I should see "This value should not be blank."
    Then I type "Shopping List 2" in "Shopping List Edit Title Field"
    And I should see "Signed in as: Amanda Cole"
    And I click "Save"
    And I should see "Record has been successfully updated"
    And I should see "Signed in as: Amanda Cole"
