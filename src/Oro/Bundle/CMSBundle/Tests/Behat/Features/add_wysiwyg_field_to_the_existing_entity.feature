@regression
@ticket-BB-18779

Feature: Add wysiwyg field to the existing entity
  In order to have ability to add wysiwyg field
  As an Administrator
  I want to be able to add wysiwyg field to the existing entity

  Scenario: Create serialized field
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    Given I click "Create Field"
    When I fill form with:
      | Field name   | wysiwyg1         |
      | Storage Type | Serialized field |
      | Type         | WYSIWYG          |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should see wysiwyg1 in grid

  Scenario: Create table column field
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "User"
    And click View User in grid
    Given I click "Create Field"
    When I fill form with:
      | Field name   | wysiwyg2     |
      | Storage Type | Table column |
      | Type         | WYSIWYG      |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should see wysiwyg2 in grid
    And I should see "Update Schema"
    When I click "Update schema"
    And I click "Yes, Proceed"
    Then I should see Schema updated flash message
