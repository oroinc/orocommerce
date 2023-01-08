@regression
@ticket-BB-16018
@ticket-BB-16930
@fixture-OroWebCatalogBundle:web_catalog.yml
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
Feature: Override product variations functionality
  In order to manage simple products in web catalog
  As administrator
  I need to be able to use override product variants configuration option to make simple products
  in category or product collection are visible on storefront

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Prepare first product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Products / Product Attributes
    And I click "Import file"
    And I upload "override_product_variations_functionality_attributes.csv" file to "ShoppingListImportFileField"
    And I click "Import file"
    And I reload the page
    And I confirm schema update

  Scenario: Update product family
    Given I proceed as the Admin
    And I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare products
    And I go to Products / Master Catalog
    And I click "Import file"
    And I upload "override_product_variations_functionality_category.csv" file to "ShoppingListImportFileField"
    And I click "Import file"
    And I go to Products / Products
    And I click "Import file"
    And I upload "override_product_variations_functionality_products.csv" file to "ShoppingListImportFileField"
    And I click "Import file"

  Scenario: Use override product variant configuration option to show simple products on storefront
    Given I proceed as the Admin
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid

    When I click on "Show Variants Dropdown"
    And I click on "First Content Variant Expand Button"
    And I fill "Content Node Form" with:
      | Titles            | Home page                               |
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    And I save form
    Then I should see "Content Node has been saved" flash message
    When I click "Create Content Node"
    And I fill "Content Node Form" with:
      | Titles   | TEST      |
      | Url Slug | test-node |
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    Then the "Override Product Variant Configuration" checkbox should be unchecked
    When I check "Override Product Variant Configuration"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "1GB" in "value"
    And I click on "Preview Results"
    Then I should see following grid:
      | SKU   | NAME                 |
      | 1GB81 | Black Slip-On Clog L |
      | 1GB82 | White Slip-On Clog M |
      | 1GB83 | Slip-On Clog         |
    When I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the Buyer
    And I am on homepage
    And I click "TEST"
    Then I should see "1GB81" product
    And I should see "1GB82" product
    And I should see "1GB83" product

  Scenario: Show that override product variant configuration option does not affects on search
    When I type "1GB" in "search"
    And I click "Search Button"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

  Scenario: Show that override product variant configuration option does not affects on All Products page
    When I proceed as the Admin
    And I go to System/Configuration
    And follow "Commerce/Catalog/Special Pages" on configuration sidebar
    And uncheck "Use default" for "Enable all products page" field
    And I check "Enable all products page"
    And save form
    Then I should see "Configuration saved" flash message
    When I go to System/Frontend Menus
    And I click view "commerce_main_menu" in grid
    And I click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title       | ALL PRODUCTS         |
      | Target Type | URI                  |
      | URI         | /catalog/allproducts |
    And save form
    Then I should see "Menu item saved successfully" flash message

    When I proceed as the Buyer
    And I reload the page
    And I click "ALL PRODUCTS"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

  Scenario: Show that override product variant configuration option does not affects on New Arrivals Block and Featured Products Block
    When I proceed as the Admin
    And I go to System/Configuration
    And follow "Commerce/Product/Promotions" on configuration sidebar
    And fill "Promotions Form" with:
      | Minimum Items Default | false |
      | Minimum Items         | 0     |
    And I save form
    Then I should see "Configuration saved" flash message

    When I proceed as the Buyer
    And I am on the homepage
    Then should not see the following products in the "New Arrivals Block":
      | SKU   |
      | 1GB81 |
      | 1GB82 |
    And should see the following products in the "New Arrivals Block":
      | SKU   |
      | 1GB83 |
    And should not see the following products in the "Featured Products Block":
      | SKU   |
      | 1GB81 |
      | 1GB82 |
    And should see the following products in the "Featured Products Block":
      | SKU   |
      | 1GB83 |

  Scenario: Remove override product variant configuration option to hide simple products on storefront
    When I proceed as the Admin
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "TEST"
    And I click on "First Content Variant Expand Button"
    And I uncheck "Override Product Variant Configuration"
    When I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the Buyer
    And I click "TEST"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

  Scenario: Check that changes of grid view does not affect on results
    When I filter SKU as contains "1GB"
    And I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

    When I click "Catalog Switcher Toggle"
    And I click "List View"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

  Scenario: Add manually added products to show these simple products on storefront regardless override option
    When I proceed as the Admin
    And I click on "First Content Variant Expand Button"
    And type "1GB81" in "value"
    And I click on "Add Button"
    And I check 1GB82 record in "Add Products Popup" grid
    And I check 1GB83 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    Then I should see following grid:
      | SKU   | NAME                 |
      | 1GB81 | Black Slip-On Clog L |
      | 1GB82 | White Slip-On Clog M |
      | 1GB83 | Slip-On Clog         |
    When I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the Buyer
    And I reload the page
    Then I should not see "1GB81" product
#    And I should see "1GB82" product
#    And I should see "1GB83" product

  Scenario: Show that manually added products functionality does not affects on search
    When I type "1GB" in "search"
    And I click "Search Button"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

  Scenario: Show that manually added products functionality does not affects on All Products page
    When I click "ALL PRODUCTS"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

  Scenario: Show that manually added products functionality does not affects on New Arrivals Block and Featured Products Block
    When I am on the homepage
    Then should not see the following products in the "New Arrivals Block":
      | SKU   |
      | 1GB81 |
      | 1GB82 |
    And should see the following products in the "New Arrivals Block":
      | SKU   |
      | 1GB83 |
    And should not see the following products in the "Featured Products Block":
      | SKU   |
      | 1GB81 |
      | 1GB82 |
    And should see the following products in the "Featured Products Block":
      | SKU   |
      | 1GB83 |

  Scenario: Show that excluded products are not shown in product collection regardless of the override option
    When I proceed as the Admin
    And I click on "First Content Variant Expand Button"
    And I check "Override Product Variant Configuration"
    And type "1GB" in "value"
    And I click "Excluded"
    And I click "Add Button"
    And I check 1GB82 record in "Add Products Popup" grid
    And I click "Add" in "UiDialog ActionPanel" element
    And I click "All Added"
    Then I should see following grid:
      | SKU   | NAME                 |
      | 1GB81 | Black Slip-On Clog L |
      | 1GB83 | Slip-On Clog         |
    When I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the Buyer
    And I click "TEST"
    Then I should see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

  Scenario: Create content node with category as content variant and check override product variant configuration option
    When I proceed as the Admin
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Create Content Node"
    And I fill "Content Node Form" with:
      | Titles   | TEST-2      |
      | Url Slug | test-2-node |
    And I click "Show Variants Dropdown"
    And I click "Add Category"
    Then the "Override Product Variant Configuration" checkbox should be unchecked
    When I click "Clogs"
    And I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the Buyer
    And I reload the page
    And I click "TEST-2"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

    When I proceed as the Admin
    And I click on "First Content Variant Expand Button"
    And I check "Override Product Variant Configuration"
    And I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the Buyer
    And I reload the page
    Then I should see "1GB81" product
    And I should see "1GB82" product
    And I should see "1GB83" product

  Scenario: Show that override product variant configuration option for category content variant does not affects on search
    When I type "1GB" in "search"
    And I click "Search Button"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

  Scenario: Show that override product variant configuration option for category content variant does not affects on All Products page
    When I click "ALL PRODUCTS"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product

  Scenario: Show that override product variant configuration option for category content variant does not affects on New Arrivals Block and Featured Products Block
    When I am on the homepage
    Then should not see the following products in the "New Arrivals Block":
      | SKU   |
      | 1GB81 |
      | 1GB82 |
    And should see the following products in the "New Arrivals Block":
      | SKU   |
      | 1GB83 |
    And should not see the following products in the "Featured Products Block":
      | SKU   |
      | 1GB81 |
      | 1GB82 |
    And should see the following products in the "Featured Products Block":
      | SKU   |
      | 1GB83 |

  Scenario: Ensure that unchecked override option does not affect when system configuration is set to "Show everywhere"
    When I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And I fill "Display Simple Variations Form" with:
      | Display Simple Variations Default | false      |
      | Display Simple Variations         | Everywhere |
    And I save form
    Then I should see "Configuration saved" flash message

    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "TEST"
    And I click on "First Content Variant Expand Button"
    And I uncheck "Override Product Variant Configuration"
    And I click "Excluded"
    And I click Remove on 1GB82 in grid "Active Grid"
    And I click "Manually Added"
    And I click Reset to Default on 1GB83 in grid "Active Grid"
    And I save form
    Then I should see "Content Node has been saved" flash message
    When I click "TEST-2"
    And I click on "First Content Variant Expand Button"
    And I uncheck "Override Product Variant Configuration"
    And I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the Buyer
    And I click "TEST"
    Then I should see "1GB81" product
    And I should see "1GB82" product
    And I should see "1GB83" product
    When I click "TEST-2"
    Then I should see "1GB81" product
    And I should see "1GB82" product
    And I should see "1GB83" product

  Scenario: Ensure that override checkbox is related for certain content variant and don't affect other variants
    When I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And I fill "Display Simple Variations Form" with:
      | Display Simple Variations Default | false           |
      | Display Simple Variations         | Hide completely |
    And I save form
    Then I should see "Configuration saved" flash message

    When I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "TEST"
    And I click on "Show Variants Dropdown"
    And I click "Add Product Collection"
    And I check "Override Product Variant Configuration"
    And I click on "Advanced Filter"
    And I drag and drop "Field Condition" on "Drop condition here"
    And I click "Choose a field.."
    And I click on "SKU"
    And type "1GB" in "value"
    And I click on "Preview Results"
    Then I should see following grid:
      | SKU   | NAME                 |
      | 1GB81 | Black Slip-On Clog L |
      | 1GB82 | White Slip-On Clog M |
      | 1GB83 | Slip-On Clog         |
    When I fill "Content Node Form" with:
      | First Content Variant Restrictions Customer | first customer |
    And I save form
    Then I should see "Content Node has been saved" flash message

    When I proceed as the Buyer
    And I click "TEST"
    Then I should not see "1GB81" product
    And I should not see "1GB82" product
    And I should see "1GB83" product
