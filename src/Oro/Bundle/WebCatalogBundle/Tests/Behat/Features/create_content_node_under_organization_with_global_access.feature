@ticket-BB-14389
@fixture-OroWebCatalogBundle:customer.yml
@fixture-OroWebCatalogBundle:web_catalog.yml
Feature: Create Content Node under Organization with Global Access
  In order to create Content Node
  As site administrator
  I need to be able to create Content Node under Organization with Global Access

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable Global Access for Organization
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/ User Management/ Organizations
    And I click "edit" on first row in grid
    And I fill form with:
        | Global Access | true |
    When I save and close form
    Then I should see "Organization saved" flash message

  Scenario: Prepare Web Catalog
    Given I set "Default Web Catalog" as default web catalog
    And I go to Marketing/ Landing Pages
    And click edit "About" in grid
    And I fill "Landing Page Form" with:
      | Titles   | Aboutt |
      | URL Slug | aboutt |
    And save and close form
    And click "Apply"
    And I go to Marketing/ Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Create new content node
    Given I click "Create Content Node"
    And I fill "Content Node" with:
        | Title | Test |
        | Slug  | test |
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Landing Page | Abo |
    And I click "Save"
    And I uncheck "Inherit Parent" element
    And I fill "Content Node Form" with:
        | Content Node Restrictions Customer | Company A |
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Open Content Node on a storefront as a Customer User
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I am on "/test"
    Then I should not see "404 Not Found"
