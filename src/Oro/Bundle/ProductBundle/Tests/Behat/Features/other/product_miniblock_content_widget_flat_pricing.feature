@behat-test-env
@ticket-BB-17550
@pricing-storage-flat
@fixture-OroProductBundle:product_with_price.yml

Feature: Product mini-block content widget flat pricing
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
    And I fill in WYSIWYG "CMS Page Content" with "<div class=\"row\"><div class=\"cell\"><h1>Additional test data</h1><div class=\"row\"><div class=\"cell\"><div data-title=\"product_mini_block\" data-type=\"product_mini_block\" class=\"content-widget content-placeholder\">{{ widget(\"product_mini_block\") }}</div></div><div class=\"cell\"></div></div></div><div class=\"cell\"></div></div>"
    When I save form
    Then I should see "Page has been saved" flash message
    And I should see URL Slug field filled with "product-mini-block-page"

  Scenario: Create Menu Item
    Given I go to System/Frontend Menus
    And click "view" on row "commerce_main_menu" in grid
    And click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | Product Mini-Block Page |
      | Target Type | URI                     |
      | URI         | product-mini-block-page |
    And I save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: Check content widget on storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I click "Product Mini-Block Page"
    Then Page title equals to "Product Mini-Block Page"
    And I should see "Product1"
    And I should see "Your Price: $10.00 / item" for "PSKU1" product
    And I should see "Add to Shopping List"

  Scenario: Check add button
    Given I click "Add to Shopping List" for "PSKU1" product
    When I click "In Shopping List" for "PSKU1" product
    Then I should see "UiDialog" with elements:
      | Title | Product1 |
    And I close ui dialog

  Scenario: Add price list restriction to guest customer group
    Given I proceed as the Admin
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And fill "Customer Group Form" with:
      | Price List | second price list |
    When I save and close form
    Then I should see "Customer group has been saved" flash message

  Scenario: Check prices is not available for guest with restricted price list but enabled in widget
    Given I proceed as the Buyer
    And I click "Sign Out"
    When I click "Product Mini-Block Page"
    Then Page title equals to "Product Mini-Block Page"
    And I should see "Product1"
    And I should not see "Your Price: $10.00 / item" for "PSKU1" product
    And I should see "Price not available"

  Scenario: Disable rendering prices via widget option
    Given I proceed as the Admin
    And go to Marketing/Content Widgets
    And click "Edit" on row "product_mini_block" in grid
    When fill "Content Widget Form" with:
      | Show Prices | false |
    And I save and close form
    Then I should see "Content widget has been saved" flash message

  Scenario: Check rendering prices on storefront disabled for logged user
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click "Product Mini-Block Page"
    Then Page title equals to "Product Mini-Block Page"
    And I should see "Product1"
    And I should not see "Your Price: $10.00 / item" for "PSKU1" product
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

  Scenario: As the admin disable product
    Given I proceed as the Admin
    And I go to Products/Products
    And click "edit" on row "PSKU1" in grid
    And fill form with:
      | Status | Disabled |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check content widget on storefront is not rendered for disabled product
    Given I proceed as the Buyer
    When I click "Product Mini-Block Page"
    Then Page title equals to "Product Mini-Block Page"
    And I should not see "Product1"
    And I should see "Additional test data"

  Scenario: As the admin set inventory status which should hide product
    Given I proceed as the Admin
    And I go to Products/Products
    And click "edit" on row "PSKU1" in grid
    And fill form with:
      | Status           | Enabled      |
      | Inventory Status | Discontinued |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check content widget on storefront is not rendered for disabled inventory status
    Given I proceed as the Buyer
    When I click "Product Mini-Block Page"
    Then Page title equals to "Product Mini-Block Page"
    And I should not see "Product1"
    And I should see "Additional test data"

  Scenario: As the admin set product visibility config option to to hidden
    Given I proceed as the Admin
    And I go to Products/Products
    And click "edit" on row "PSKU1" in grid
    And fill form with:
      | Inventory Status | In Stock |
    When I save and close form
    Then I should see "Product has been saved" flash message
    And go to System / Configuration
    And follow "Commerce/Customer/Visibility" on configuration sidebar
    And fill "Visibility Settings Form" with:
      | Product Visibility Use Default | false  |
      | Product Visibility             | hidden |
    When I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Check content widget on storefront is not rendered for hidden product visibility
    Given I proceed as the Buyer
    When I click "Product Mini-Block Page"
    Then Page title equals to "Product Mini-Block Page"
    And I should not see "Product1"
    And I should see "Additional test data"
