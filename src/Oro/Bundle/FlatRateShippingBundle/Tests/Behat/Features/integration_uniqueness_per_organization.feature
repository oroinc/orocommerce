@regression
@ticket-BB-16314
@fixture-OroOrganizationProBundle:SecondOrganizationFixture.yml
Feature: Integration uniqueness per Organization
  In order to manage integrations
  As administrator
  I need to manage integrations records per organization

  Scenario: Create Integration in the "ORO" Organization
    Given I login as administrator
    And go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    And fill "Integration Form" with:
      | Type  | Flat Rate Shipping |
      | Name  | Flat Rate          |
      | Label | Flat_Rate          |
    When I save and close form
    Then I should see "Integration saved" flash message

  Scenario: Create Integration with the same name in the "ORO Pro" Organization
    Given I am logged in under ORO Pro organization
    And go to System/ Integrations/ Manage Integrations
    And click "Create Integration"
    And fill "Integration Form" with:
      | Type  | Flat Rate Shipping |
      | Name  | Flat Rate          |
      | Label | Flat_Rate          |
    When I save and close form
    Then I should see "Integration saved" flash message

  Scenario: Create Integration with the same name in the "ORO Pro" Organization once more
    Given I click "Create Integration"
    And fill "Integration Form" with:
      | Type  | Flat Rate Shipping |
      | Name  | Flat Rate          |
      | Label | Flat_Rate          |
    When I save and close form
    Then I should see validation errors:
      | Name | This value is already used. |
