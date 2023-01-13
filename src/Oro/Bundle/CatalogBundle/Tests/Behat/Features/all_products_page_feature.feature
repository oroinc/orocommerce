@ticket-BB-11551
@ticket-BB-16275
@fixture-OroWebCatalogBundle:empty_web_catalog.yml
@fixture-OroCatalogBundle:all_products_page.yml

Feature: All products page feature
  In order to display all products of small e-shop
  As a merchant
  I want to have a page that displays all products grouped by category

  Scenario: Create different window session
    Given sessions active:
      | Admin          |first_session |
      | User           |second_session|

  Scenario: Administrator enables all products feature
    Given I proceed as the Admin
    And login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Catalog/Special Pages" on configuration sidebar
    And uncheck "Use default" for "Enable all products page" field
    And I check "Enable all products page"
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: I add link to all products page to the main menu
    Given I proceed as the Admin
    When I go to System/ Frontend Menus
    And I click view "commerce_main_menu" in grid
    And I click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | All Products         |
      | Target Type | URI                  |
      | URI         | /catalog/allproducts |
    And save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: I add new menu item with all products page via Web Catalogs
    Given I proceed as the Admin
    Then I set "Default Web Catalog" as default web catalog
    And I go to Marketing/ Web Catalogs
    And I click view "Default Web Catalog" in grid
    And I click "Edit Content Tree"
    And I fill "Content Node Form" with:
      | Meta Title  | Root node |
    And I save form
    Then I should see "Content Node has been saved" flash message
    And I click "Create Content Node"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles                   | All Products (Web Catalog)                                   |
      | Url Slug                 | allproducts                                                  |
      | System Page Route        | Oro Catalog Frontend Product Allproducts (All products page) |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: User opens All product page and checked that all category showed in same order
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    Then I go to the homepage
    Then I click "All Products"
    And I should see following categories in same order:
      | NewCategory  |
      | NewCategory2 |
      | NewCategory3 |
    Then I click "All Products (Web Catalog)"
    And I should see following categories in same order:
      | NewCategory  |
      | NewCategory2 |
      | NewCategory3 |

  Scenario: User adds product to shopping list
    Given I proceed as the User
    And I click "All Products"
    # Filtering by full product name "Product3`\"'&йёщ®&reg;>" does not work on elasticsearch, see BB-19131
    When I filter Name as contains "Product3`\"'&йёщ®"
    And I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    When I click "Shopping List"
    And I should see "Product3`\"'&йёщ®&reg;>"
    Then I click "All Products"
    # Filtering by full product name "Product3`\"'&йёщ®&reg;>" does not work on elasticsearch, see BB-19131
    When I filter Name as contains "Product3`\"'&йёщ®"
    Then I should see "In Shopping List"

  Scenario: User filters products and hide categories except one
    Given I proceed as the User
    Given I click "All Products"
    And I filter Name as contains "Product1"
    And I should not see "Product2"
    And I should not see "Product3`\"'&йёщ®&reg;>"
    And I should see "NewCategory"
    And I should not see "NewCategory2"
    And I should not see "NewCategory3"

  Scenario: Administrator disables All products page feature
    Given I proceed as the Admin
    And I go to System/ Configuration
    And follow "Commerce/Catalog/Special Pages" on configuration sidebar
    And check "Use default" for "Enable all products page" field
    And I save form
    Then I should see "Configuration saved" flash message
    Given I proceed as the User
    When I am on the homepage
    Then I click "All Products"
    And I should see "404 Not Found"
    Then I click "All Products (Web Catalog)"
    And I should see "404 Not Found"

  @skip
  Scenario: Installation with or without demo data
    Given I'm performing application installation with demo data
    When I login as "OroAdmin" user
    And I go to System/ Configuration
    And I click "COMMERCE"
    And I click "Catalog"
    And I click "Special Pages"
    Then I should see feature enabled
    But I'm performing application installation without demo data
    When I login as "Administrator" user
    And I go to System/ Configuration
    And I click "COMMERCE"
    And I click "Catalog"
    And I click "Special Pages"
    Then I should see feature disabled
