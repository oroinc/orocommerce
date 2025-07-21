@regression
@ticket-BAP-17292
@automatically-ticket-tagged
Feature: Create notification rule for entity with biderectional extend relation
  In Order to manage Email notification rules
  As an Administrator
  I want to be able to use entities with bidirectional extend relations

  Scenario: Create Extend Entity with one-to-many relation to User entity
    Given I login as administrator
    And I go to System/Entities/Entity Management
    And filter Name as is equal to "Group"
    Then click View Group in grid
    When I click "Create Field"
    And I fill form with:
      | Field name | TestName   |
      | Type       | String |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    When I click "Create Field"
    And I fill form with:
      | Field name | assigned_to |
      | Type       | One to many |
    And I click "Continue"
    And I fill form with:
      | Target Entity              | User          |
      | Related Entity Data Fields | Id            |
      | Related Entity Info Title  | First name    |
      | Related Entity Detailed    | Primary Email |
    And I save and close form
    Then I should see "Field saved" flash message
    And I should see "Update Schema"
    When I click update schema
    And I should see Schema updated flash message

  Scenario: Create Email Notification Rule for Order entity
    Given go to System/ Emails/ Notification Rules
    And click "Create Notification Rule"
    And fill form with:
      | Entity Name | Order                    |
      | Event Name  | Entity create            |
      | Template    | order_confirmation_email |
      | Groups      | Administrators           |
    When I save and close form
    Then I should see "Notification Rule saved" flash message
