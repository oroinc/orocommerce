@regression
@ticket-BAP-20919

Feature: Page Localized Fields Auditable
  In order to have page localized fields auditable properly
  As an Administrator
  I need to check the data audit settings of page fields correct

  Scenario: Login as administrator and go to entity management
    Given I login as administrator

  Scenario: Check default data audit settings of entity page localized fields
    Given I go to System/Entities/Entity Management
    When filter Name as is equal to "Page"
    And I click View Page in grid
    And I press "Fields"
    Then I should see following grid containing rows:
      | Name              | Auditable |
      | titles            | Yes       |
      | content           | No        |
