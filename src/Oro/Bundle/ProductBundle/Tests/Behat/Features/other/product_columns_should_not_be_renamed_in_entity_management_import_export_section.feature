@regression
@ticket-BB-19842
Feature: Product columns should not be renamed in entity management import export section

  Scenario: Ensure there is no Column Name input for Product columns in Import Export section
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "Product"
    And I click view Product in grid
    When I click edit "attributeFamily" in grid
    Then I should not see "Column Name Field" element inside "EntityConfigForm" element
    When I fill form with:
      | Column Position | 20  |
      | Exclude Column  | Yes |
      | Export Fields   | All |
    And I save and close form
    Then I should see "Field saved" flash message

  Scenario: Ensure there is Column Name input for any other entity columns in Import Export section
    Given I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "ProductUnit"
    And I click view ProductUnit in grid
    When I click edit "code" in grid
    Then I should see "Column Name Field" element inside "EntityConfigForm" element
    When I fill form with:
      | Column Name     | Code Renamed |
      | Column Position | 20           |
      | Exclude Column  | Yes          |
    And I save and close form
    Then I should see "Field saved" flash message
