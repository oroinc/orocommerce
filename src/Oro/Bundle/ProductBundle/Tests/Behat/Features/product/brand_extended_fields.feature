@ticket-BB-16205
Feature: Brand extended fields
  In order to extend brands functionality
  As an Administrator
  I want to be able to add new fields to brand entity

  Scenario: Add new field to brand entity
    Given I login as administrator
    And I go to System/ Entities/ Entity Management
    And I filter Name as is equal to "Brand"
    And I click view Brand in grid
    When I click "Create field"
    And I fill form with:
      | Field name   | TableColumnStringField |
      | Storage type | Table column           |
      | Type         | String                 |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Check that new field is available for Brand entity
    When I go to Products/ Product Brands
    And click "Create Brand"
    Then I should see "Additional"
    And I should see "TableColumnStringField"
