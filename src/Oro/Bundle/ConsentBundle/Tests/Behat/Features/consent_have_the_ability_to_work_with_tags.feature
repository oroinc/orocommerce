@regression
@feature-BB-19444
@fixture-OroConsentBundle:ConsentTags.yml

Feature: Consent have the ability to work with tags
  In order to be able to work consent entity with tags
  As an administrator
  I create a new consent and check if can change the tags on the consent view page and consent grid

  Scenario: Enable consent functionality
    Given I login as administrator
    And go to System/ Configuration
    And follow "Commerce/Customer/Consents" on configuration sidebar
    When I fill form with:
      | Use Default                  | false |
      | Enable User Consents Feature | true  |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Enable Tags options for Consent
    Given I go to System/Entities/Entity Management
    And filter Name as is equal to "Consent"
    When I click Edit Consent in grid
    And select "Yes" from "Enable Tags"
    And save and close form
    Then I should see "Entity saved" flash message

  Scenario: Create Consent
    Given I go to System/ Consent Management
    And click "Create Consent"
    When I fill "Consent Form" with:
      | Name | Consent |
    And save and close form
    Then I should see "Consent has been created" flash message

  Scenario: Edit tag in view page
    Given I should see "Name Consent"
    And should see "Tags N/A"
    When I press "Edit Tags Button"
    And fill "View Page Tags Form" with:
      | Tags | [FirstTag] |
    And submit form
    Then I should see "Record has been successfully updated" flash message
    And should see "Tags FirstTag"

  Scenario: Edit tag in grid
    Given I go to System/ Consent Management
    When I edit Tags as "SecondTag"
    Then should see "Record has been successfully updated" flash message
    When I reload the page
    Then should see following grid:
      | Name    | Tags               |
      | Consent | FirstTag SecondTag |
