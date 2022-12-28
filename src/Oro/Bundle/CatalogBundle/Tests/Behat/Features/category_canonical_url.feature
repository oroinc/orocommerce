@regression
@ticket-BB-18600
@fixture-OroWebCatalogBundle:web_catalog.yml
@fixture-OroCatalogBundle:categories.yml

Feature: Category canonical url

  Scenario: Create two sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create content node for web catalog
    And I proceed as the Admin
    And I login as administrator
    Given I set "Default Web Catalog" as default web catalog
    And I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I fill "Content Node" with:
      | Title | Default Web Catalog |
    And I click "Save"
    And I should see "Content Node has been saved" flash message
    When I click "Create Content Node"
    And I fill "Content Node" with:
      | Title      | Printers              |
      | Slug       | web-catalog-printers  |
      | Meta Title | Best printers for you |
    And I click on "Show Variants Dropdown"
    And I click "Add Category"
    And I expand "Retail Supplies" in tree
    Then I should see "All Products Lighting Products Retail Supplies Printers"
    And I should see "Retail Supplies" after "Lighting Products" in tree
    And I click "Printers"
    When I click "Save"
    Then I should see "Content Node has been saved" flash message

  Scenario: Check canonical URL of the front store web catalog node page
    Given I proceed as the Buyer
    And I am on homepage
    And I should see "Printers" in main menu
    When I click "Printers"
    Then Page should contain Canonical URL with URI "web-catalog-printers"

  Scenario: Configure Canonical URL to use Direct URLs only
    Given I proceed as the Admin
    When I go to System/ Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Canonical URL Type" field
    And I fill in "Canonical URL Type" with "Direct URL"
    And uncheck "Use default" for "Prefer Self-Contained Web Catalog Canonical URLs" field
    And I uncheck "Prefer Self-Contained Web Catalog Canonical URLs"
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check canonical URL of the front store web catalog node page
    Given I proceed as the Buyer
    And I reload the page
    Then Page should contain Canonical URL with URI "printers"

  Scenario: Change content node category to not include subcategories and check the canonical URL
    Given I proceed as the Admin
    And I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I click "Printers"
    And I click "First Content Variant Expand Button"
    And I fill "Content Node Form" with:
      | Sub-Categories | Do not include |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check canonical URL of the front store web catalog node page
    Then I proceed as the Buyer
    And I reload the page
    And I assert canonical URL for "Printers" category is a System URL not including subcategories

  Scenario: Configure Canonical URL to use System URLs only
    Given I proceed as the Admin
    When I go to System/ Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    And I fill in "Canonical URL Type" with "System URL"
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check canonical URL of the front store web catalog node page
    Then I proceed as the Buyer
    And I reload the page
    And I assert canonical URL for "Printers" category is a System URL not including subcategories

  Scenario: Change content node category to include subcategories and check the canonical URL
    Given I proceed as the Admin
    And I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I click "Printers"
    And I click "First Content Variant Expand Button"
    And I fill "Content Node Form" with:
      | Sub-Categories | Include, show as filter |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check canonical URL of the front store web catalog node page
    Given I proceed as the Buyer
    And I reload the page
    Then I should not see "404 Not Found"
    And I assert canonical URL for "Printers" category is a System URL including subcategories
