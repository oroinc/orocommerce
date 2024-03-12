@ticket-BB-15291
@fixture-OroUserBundle:user.yml
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml

Feature: Change default content variant for the content node
  In order to change default content variant for content node
  As an Administrator
  I should be able to change default content variant for content node
  As an Guest User
  I want to be abble to see default content variant for content node on frontstore
  As an Buyer
  I want to be abble to see default content variant for content node restricted to buer on frontstore

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
      | Buyer | system_session |

  Scenario: Create content node with 2 variants
    Given I proceed as the Admin
    And login as administrator
    And I set "Default Web Catalog" as default web catalog
    When I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I fill "Content Node" with:
      | Title | 220 Lumen Rechargeable Headlamp |
    Then I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    When I click "Manually Added"
    And I click on "Add Button"
    Then I should see "Add Products"
    When I check PSKU1 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following "Active Grid" grid:
      | SKU    | NAME                            |
      | PSKU1  | 220 Lumen Rechargeable Headlamp |
    When I click "Save"
    Then I should see "Content Node has been saved" flash message
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Landing Page | Cookie Policy |
    And I fill "Content Node Form" with:
      | First Content Variant Restrictions Customer | first customer |
    When I click "Save"
    Then I should see "Content Node has been saved" flash message

  Scenario: Check content node default variant by Guest user
    Given I proceed as the Guest
    When I go to the homepage
    Then I should see "220 Lumen Rechargeable Headlamp"
    And I should not see "This is the Cookie Policy for OroCommerce application."

  Scenario: Check content node default variant by Amanda
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    Then I go to the homepage
    And I should see "220 Lumen Rechargeable Headlamp"
    And I should see "This is the Cookie Policy for OroCommerce application."

  Scenario: Change default content variant to landing page
    Given I proceed as the Admin
    When I click on "Default Content Variant Expand Button"
    And I click "Content Node Default Second Content Variant"
    Then I fill "Content Node Form" with:
      | First Content Variant Restrictions Customer | first customer |
    When I click "Save"
    Then I should see "Content Node has been saved" flash message

  Scenario: Check content node default variant by Guest user
    Given I proceed as the Guest
    When I go to the homepage
    And I should see "220 Lumen Rechargeable Headlamp"
    And I should see "This is the Cookie Policy for OroCommerce application."

  Scenario: Check content node default variant by Amanda
    Given I proceed as the Buyer
    When I go to the homepage
    Then I should see "220 Lumen Rechargeable Headlamp"
    And I should not see "This is the Cookie Policy for OroCommerce application."
