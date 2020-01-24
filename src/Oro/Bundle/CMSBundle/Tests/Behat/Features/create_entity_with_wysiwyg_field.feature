@ticket-BB-18779

Feature: Create entity with wysiwyg field
  In order to have ability to create custom entity with wysiwyg field
  As an Administrator
  I want to be able to create custom entity with wysiwyg field

  Scenario: Create custom entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And I click "Create Entity"
    When I fill form with:
      | Name         | entity1 |
      | Label        | label1  |
      | Plural Label | plural1 |
    And I save and close form
    Then I should see "Entity saved" flash message
    And I should not see "Update Schema"

  Scenario: Create serialized field
    Given I click "Create Field"
    When I fill form with:
      | Field name   | wysiwyg1         |
      | Storage Type | Serialized field |
      | Type         | WYSIWYG          |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should see "Update Schema"
    When I click "Update schema"
    And I click "Yes, Proceed"
    Then I should see Schema updated flash message

  Scenario: Create another custom entity
    Given I click "Create Entity"
    When I fill form with:
      | Name         | entity2 |
      | Label        | label2  |
      | Plural Label | plural2 |
    And I save and close form
    Then I should see "Entity saved" flash message
    And I should not see "Update Schema"

  Scenario: Create table column field
    Given I click "Create Field"
    When I fill form with:
      | Field name   | wysiwyg2     |
      | Storage Type | Table column |
      | Type         | WYSIWYG      |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    And I should see "Update Schema"
    When I click "Update schema"
    And I click "Yes, Proceed"
    Then I should see Schema updated flash message
