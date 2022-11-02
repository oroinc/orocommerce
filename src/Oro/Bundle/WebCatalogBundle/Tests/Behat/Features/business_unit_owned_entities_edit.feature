@regression
@ticket-BB-15243
@automatically-ticket-tagged
@fixture-OroUserBundle:user.yml
@fixture-OroWebCatalogBundle:web_catalog.yml
Feature: Business Unit owned entities edit
  As Web Catalog Manager
  I need to edit business unit owned entities

  Scenario: Web Catalogs should be editable by Web Catalog Manager
    Given I login as administrator
    And I go to System/ User Management/ Roles
    And I click clone Catalog Manager in grid
    And I fill in "Role" with "Web Catalog Manager"
    And select following permissions:
      | Web Catalog | View:Business Unit | Edit:Business Unit |
    And I save and close form
    And I go to System/ User Management/ Users
    And I click edit charlie in grid
    And I fill "User Form" with:
      | Web Catalog Manager | true |
    And I save and close form

    And I login as "charlie" user
    And I go to Marketing/ Web Catalogs
    When click edit Default Web Catalog in grid
    And I fill form with:
      | Description | Updated |
    When I save and close form
    Then I should see "Web Catalog has been saved"
