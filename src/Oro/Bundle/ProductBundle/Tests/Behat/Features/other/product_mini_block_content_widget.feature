@ticket-BB-17550
@fixture-OroProductBundle:products.yml

Feature: Product mini-block content widget
  In order to have product mini-block displayed on the storefront
  As an Administrator
  I need to be able to create and modify the product mini-block widget in the back office

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create content widget
    Given I proceed as the Admin
    And login as administrator
    And go to Marketing/Content Widgets
    And click "Create Content Widget"
    When fill "Content Widget Form" with:
      | Type            | Product Mini-Block |
      | Name            | product_mini_block |
      | Product         | Product1           |
      | Show Prices     | true               |
      | Show Add Button | true               |
    And I save and close form
    Then I should see "Content widget has been saved" flash message
    And I should see "Type: Product Mini-Block"
    And I should see Content Widget with:
      | Name            | product_mini_block |
      | Product         | Product1           |
      | Show Prices     | Yes                |
      | Show Add Button | Yes                |

  Scenario: Create Landing Page
    Given I go to Marketing/Landing Pages
    And click "Create Landing Page"
    And I fill in Landing Page Titles field with "Product Mini-Block Page"
    And I fill in WYSIWYG "CMS Page Content" with "<div class=\"row\"><div class=\"cell\"><div class=\"row\"><div class=\"cell\"><div data-title=\"product_mini_block\" data-type=\"product_mini_block\" class=\"content-widget content-placeholder\">{{ widget(\"product_mini_block\") }}</div></div><div class=\"cell\"></div></div></div><div class=\"cell\"></div></div>"
    When I save form
    Then I should see "Page has been saved" flash message
    And I should see URL Slug field filled with "product-mini-block-page"

  Scenario: Create Menu Item
    Given I go to System/Frontend Menus
    And click "view" on row "commerce_main_menu" in grid
    And click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title | Product Mini-Block Page |
      | URI   | product-mini-block-page |
    And I save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: Check content widget on storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I click "Product Mini-Block Page"
    Then Page title equals to "Product Mini-Block Page"
    And I should see "Product1"
    And I should see "Price not available"
    And I should see "Add to Shopping List"

  Scenario: Check add button
    Given I click "Add to Shopping List" for "PSKU1" product
    When I click "In Shopping List" for "PSKU1" product
    Then I should see "UiDialog" with elements:
      | Title | Product1 |
    And I close ui dialog

  Scenario: Disable rendering prices
    Given I proceed as the Admin
    And go to Marketing/Content Widgets
    And click "Edit" on row "product_mini_block" in grid
    When fill "Content Widget Form" with:
      | Show Prices | false |
    And I save and close form
    Then I should see "Content widget has been saved" flash message

  Scenario: Disable rendering prices of storefront
    Given I proceed as the Buyer
    When reload the page
    Then Page title equals to "Product Mini-Block Page"
    And I should see "Product1"
    And I should not see "Price not available"
    And I should see "In Shopping List"
    And I should see "Update Shopping List"

  Scenario: Disable rendering buttons
    Given I proceed as the Admin
    And go to Marketing/Content Widgets
    And click "Edit" on row "product_mini_block" in grid
    When fill "Content Widget Form" with:
      | Show Add Button | false |
    And I save and close form
    Then I should see "Content widget has been saved" flash message

  Scenario: Disable rendering prices of storefront
    Given I proceed as the Buyer
    When reload the page
    Then Page title equals to "Product Mini-Block Page"
    And I should see "Product1"
    And I should not see "Price not available"
    And I should not see "In Shopping List"
    And I should not see "Update Shopping List"
