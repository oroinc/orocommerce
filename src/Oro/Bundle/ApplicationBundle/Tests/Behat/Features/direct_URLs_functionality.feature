@ticket-BAP-18983
@regression
Feature: Direct URLs functionality

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Check Product and Category URLs(Enable Direct URLs - enabled)
    Given I proceed as the Admin
    And I login as administrator
    When go to Products/ Master Catalog
    And click "Create Category"
    And fill "Create Category Form" with:
      | Title               | Lenovo Vibe |
      | Inventory Threshold | 0           |
    And click "Save"
    And go to Products/ Products
    And click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Lenovo Vibe"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | Lenovo_Vibe_sku |
      | Name             | Lenovo Vibe     |
      | Status           | Enable          |
      | Unit Of Quantity | item            |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 100                |
      | Currency   | $                  |
    And save and close form
    And go to System/Configuration
    And follow "Commerce/Catalog/Special Pages" on configuration sidebar
    And uncheck "Use default" for "Enable all products page" field
    And I check "Enable all products page"
    And save form
    And I should see "Configuration saved" flash message
    And I go to System/ Frontend Menus
    And I click view "commerce_main_menu" in grid
    And I click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | All Products         |
      | Target Type | URI                  |
      | URI         | /catalog/allproducts |
    And save form
    Then I should see "Menu item saved successfully" flash message
    And I proceed as the User
    And I am on the homepage
    And click "All Products"
    When click "View Details" for "Lenovo Vibe" product
    Then I should be on "/lenovo-vibe-1"
    When click "Lenovo Vibe"
    Then I should be on "/lenovo-vibe"

  Scenario: Check Product and Category URLs (Enable Direct URLs - disabled)
    Given I proceed as the Admin
    And go to System/Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    When I fill "Direct URLs Form" with:
      | Enable Direct URLs Use Default | false |
      | Enable Direct URLs             | false |
    And click "Save settings"
    And I should see "Configuration saved" flash message
    And I proceed as the User
    When I reload the page
    Then should see "404 Not Found"
    And click "All Products"
    When click "View Details" for "Lenovo Vibe" product
    Then I should be on "/product/view/1"
    When click "Lenovo Vibe"
    Then I should be on "/product/?categoryId=2&includeSubcategories=1"

  Scenario: Check Product and Category URLs Prefix (Enable Direct URLs - enabled)
    Given I proceed as the Admin
    And go to System/Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    When I fill "Direct URLs Form" with:
      | Enable Direct URLs Use Default      | false          |
      | Enable Direct URLs                  | true           |
      | Product URL Prefix Use Default      | false          |
      | Product URL Prefix                  | ProdPrefix     |
      | Category URL Prefix Use Default | false          |
      | Category URL Prefix             | CategoryPrefix |
    And click "Save settings"
    And I should see "Configuration saved" flash message
    And I proceed as the User
    When I reload the page
    Then should not see "404 Not Found"
    And click "All Products"
    When click "View Details" for "Lenovo Vibe" product
    Then I should be on "/ProdPrefix/lenovo-vibe-1"
    When click "Lenovo Vibe"
    Then I should be on "/CategoryPrefix/lenovo-vibe"

  Scenario: Check that it possible to set same URL Slug for Product and Category if they have URLs Prefix (Enable Direct URLs - enabled)
    Given I proceed as the Admin
    And go to Products/ Products
    And click edit "Lenovo Vibe" in grid
    When fill "Product Form" with:
      | URL Slug | lenovo-vibe |
    And save and close form
    And click "Apply"
    And I proceed as the User
    And I reload the page
    And click "All Products"
    And click "View Details" for "Lenovo Vibe" product
    Then I should be on "/ProdPrefix/lenovo-vibe"
    When click "Lenovo Vibe"
    Then I should be on "/CategoryPrefix/lenovo-vibe"
    When click "View Details" for "Lenovo Vibe" product
    Then I should be on "/CategoryPrefix/lenovo-vibe/_item/lenovo-vibe"
