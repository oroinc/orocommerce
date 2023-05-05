@ticket-BB-13519
@automatically-ticket-tagged
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:default_webcatalog.yml
Feature: Restriction by non-autenticated customer group
  In order to manipulate content variant visibility
  As site administrator
  I need to be able to add content variant which is visible to non-authenticated customer group

  Scenario: Create default system page and system page which is shown to non-authenticated customer group
    Given I login as administrator
    And I set "Default Web Catalog" as default web catalog
    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles                           | Contact Us Node                                   |
      | System Page Route                | Oro Contactus Bridge Contact Us Page (Contact Us) |
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles                           | Welcome Node                            |
      | System Page Route                | Oro Frontend Root (Welcome - Home page) |
      | First System Page Customer Group | Non-Authenticated Visitors              |
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Non authenticated user sees home page
    When I am on homepage
    Then I should see "FEATURED CATEGORIES"

  Scenario: Authenticated user sees contact us page
    When I login as AmandaRCole@example.org buyer
    Then I should not see "FEATURED CATEGORIES"
    And I should see "CONTACT US"
