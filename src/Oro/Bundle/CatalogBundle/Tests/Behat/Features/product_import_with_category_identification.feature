@ticket-BB-18280
@fixture-OroCatalogBundle:product_import_category_identification.yml

Feature: Product Import with Category Identification
  In order to import products with categories
  As an Administrator
  I want to be able to import products using category ID, category path, or category title
  And I want to control how the system handles non-unique titles and ID/title mismatches

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session |

  # ========================================
  # Part 1: Import by Category Title
  # ========================================

  Scenario: Import product with unique category title
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/ Products
    And I open "Products" import tab
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | SKU   | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU1 | Product 1          |             | Electronics            | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    And I import file
    Then I should receive the import results email with no errors and containing the text "processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU1" in grid
    Then I should see "Electronics"

  Scenario: Import product with subcategory title
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU   | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU2 | Product 2          |             | Laptops                | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU2" in grid
    Then I should see "Laptops"

  Scenario: Import product with non-existent category title should fail
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU   | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU3 | Product 3          |             | NonExistentCategory    | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email containing the text "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0"
    When I follow "Error log" link from the email
    Then I should see import error "Category \"NonExistentCategory\" not found in the master catalog"

  # ========================================
  # Part 2: Import by Category Path
  # ========================================

  Scenario: Import product with full category path
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU   | Name.default.value | Category.ID | category.path                        | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU4 | Product 4          |             | All Products / Electronics / Laptops | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU4" in grid
    Then I should see "Laptops"

  Scenario: Import product with category path
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU   | Name.default.value | Category.ID | category.path                        | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU5 | Product 5          |             | All Products / Electronics / Phones  | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU5" in grid
    Then I should see "Phones"

  Scenario: Import product with non-existent category path should fail
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU   | Name.default.value | Category.ID | category.path                         | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU6 | Product 6          |             | All Products / NonExistent / Category | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email containing the text "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0"
    When I follow "Error log" link from the email
    Then I should see import error "Category \"All Products / NonExistent / Category\" not found in the master catalog"

  # ========================================
  # Part 3: Non-Unique Category Handling
  # ========================================

  Scenario: Configure non-unique category title handling to fail
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/ Configuration
    And I follow "Commerce/Product/Product Import\/Export" on configuration sidebar
    And uncheck "Use default" for "Category Identification When Not Unique" field
    And I fill form with:
      | Category Identification When Not Unique | Fail if multiple matches are found |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Import product with non-unique category title should fail when configured to fail
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU   | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU7 | Product 7          |             | Accessories            | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email containing the text "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0"
    When I follow "Error log" link from the email
    Then I should see import error "Category \"Accessories\" is not unique in the master catalog"

  Scenario: Import product with non-unique path should use first match when configured
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU   | Name.default.value | Category.ID | category.path                                      | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU8 | Product 8          |             | All Products / Electronics / Laptops / Accessories | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU8" in grid
    Then I should see "Accessories"

  Scenario: Configure non-unique category title handling to use first match
    Given I go to System/ Configuration
    And I follow "Commerce/Product/Product Import\/Export" on configuration sidebar
    And I fill form with:
      | Category Identification When Not Unique | Assign the first match |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Import product with non-unique category title should use first match
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU   | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU9 | Product 9          |             | Accessories            | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU9" in grid
    Then I should see "Accessories"


  # ========================================
  # Part 4: Import by Category ID
  # ========================================
  # Note: Category IDs from the fixture are:
  #   ID 1 = "All Products" (master catalog root, created during installation)
  #   ID 2 = "Electronics"
  #   ID 3 = "Laptops"
  #   ID 4 = "Phones"
  #   ID 5 = "Accessories" (under Laptops)
  #   ID 6 = "Accessories" (under Phones)

  Scenario: Import product with category ID only
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU    | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU10 | Product 10         | 2           |                        | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU10" in grid
    Then I should see "Electronics"

  Scenario: Import product with non-existent category ID should fail
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU    | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU11 | Product 11         | 99999       |                        | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email containing the text "Errors: 1 processed: 1, read: 1, added: 0, updated: 0, replaced: 0"

  # ========================================
  # Part 5: Category ID and Title Mismatch Handling
  # ========================================

  Scenario: Configure mismatch resolution to fail on mismatch
    Given I go to System/ Configuration
    And I follow "Commerce/Product/Product Import\/Export" on configuration sidebar
    And uncheck "Use default" for "Category ID and Path/Title Mismatch Resolution" field
    And I fill form with:
      | Category ID and Path/Title Mismatch Resolution | Fail if category ID and path/title do not match |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Import product with mismatched category ID and title should fail
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU    | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU12 | Product 12         | 2           | Phones                 | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email containing the text "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0"
    When I follow "Error log" link from the email
    Then I should see import error "does not match the category with ID"

  Scenario: Configure mismatch resolution to use category ID
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/ Configuration
    And I follow "Commerce/Product/Product Import\/Export" on configuration sidebar
    And I fill form with:
      | Category ID and Path/Title Mismatch Resolution | Category ID takes precedence |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Import product with mismatched category ID and title should use ID
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU    | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU13 | Product 13         | 2           | Phones                 | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU13" in grid
    # Should see the category from ID (Electronics), not from title (Phones)
    Then I should see "Electronics"

  Scenario: Configure mismatch resolution to use category title
    Given I go to System/ Configuration
    And I follow "Commerce/Product/Product Import\/Export" on configuration sidebar
    And I fill form with:
      | Category ID and Path/Title Mismatch Resolution | Category path/title takes precedence |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Import product with mismatched category ID and title should use title
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU    | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU14 | Product 14         | 2           | Phones                 | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU14" in grid
    # Should see the category from title (Phones), not from ID (Electronics)
    Then I should see "Phones"

  # ========================================
  # Part 6: Combined Scenarios
  # ========================================

  Scenario: Import product with matching category ID and title
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU    | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU15 | Product 15         | 2           | Electronics            | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU15" in grid
    Then I should see "Electronics"

  Scenario: Import product with category ID and path (path should be ignored when ID wins)
    Given I go to System/ Configuration
    And I follow "Commerce/Product/Product Import\/Export" on configuration sidebar
    And I fill form with:
      | Category ID and Path/Title Mismatch Resolution | Category ID takes precedence |
    When I save form
    Then I should see "Configuration saved" flash message
    And I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU    | Name.default.value | Category.ID | category.path                       | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU16 | Product 16         | 2           | All Products / Electronics / Phones | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0"
    When I go to Products/ Products
    And I click view "PSKU16" in grid
    # Should see Electronics (from ID), not Phones (from path)
    Then I should see "Electronics"

  Scenario: Update existing product category using title
    Given I go to Products/ Products
    And I open "Products" import tab
    And fill template with data:
      | SKU    | Name.default.value | Category.ID | category.default.title | Product Family.Code | Status  | Type   | Inventory Status.Id | Unit of Quantity.Unit.Code | Unit of Quantity.Precision |
      | PSKU15 | Product 15         |             | Laptops                | default_family      | enabled | simple | in_stock            | set                        | 1                          |
    When I import file
    Then I should receive the import results email with no errors and containing the text "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1"
    When I go to Products/ Products
    And I click view "PSKU15" in grid
    # Category should be updated to Laptops
    Then I should see "Laptops"
