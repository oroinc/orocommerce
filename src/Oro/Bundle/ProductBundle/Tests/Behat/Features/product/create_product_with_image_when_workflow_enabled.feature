@ticket-BB-14928
@ticket-BB-18135
@fixture-OroProductBundle:ProductWorkflowFixture.yml

Feature: Create product with image when workflow enabled
  In order to ensure a product can be successfully created with image when workflow is enabled
  As an administrator
  I need to create a product and upload an image with enabled workflow for Product entity

  Scenario: Feature Background
    Given complete workflow fixture loading
    And I set configuration property "oro_attachment.original_file_names_enabled" to "0"

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Create a Product With Image
    Given I proceed as the Admin
    And I login as administrator
    And go to Products/Products
    And click "Create Product"
    And fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "ProductForm" with:
      | SKU    | PSKU1    |
      | Name   | Product1 |
      | Status | Enabled  |
    And I set Images with:
      | Main | Listing | Additional |
      | 1    | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    When I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "Product Workflow"
    And I should see "cat1.jpg"

  Scenario: Check product image URL at store front
    Given I proceed as the User
    And I am on the homepage
    When type "PSKU1" in "search"
    And click "Search Button"
    Then should see "Uploaded Product Image" for "PSKU1" product
    When click "View Details" for "PSKU1" product
    Then I should see an "Uploaded Product Image" element
    And "Uploaded Product Image" element "src" attribute should not contain "-cat1.jpg"
    And should not see an "Empty Product Image" element

  Scenario: Enable original product image file names
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Product/Product Images" on configuration sidebar
    And uncheck "Use default" for "Enable Original File Names" field
    And I check "Enable Original File Names"
    And click "Save settings"
    And should see "Configuration saved" flash message

  Scenario: Check if there Product Images with original file names on frontend
    Given I proceed as the User
    When I reload the page
    Then I should see an "Uploaded Product Image" element
    And "Uploaded Product Image" element "src" attribute should contain "-cat1.jpg"
    And should not see an "Empty Product Image" element

  Scenario: Import Products
    Given I proceed as the Admin
    And I go to Products/ Products
    When I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | names.default.value |
      | default_family       | PSKU2 | enabled | simple | in_stock            | set                            | 3                              | Product2            |
      | default_family       | PSKU3 | enabled | simple | in_stock            | item                           | 1                              | Product3            |
    And import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And reload the page
    And should see following grid:
      | SKU   | NAME     | Status  |
      | PSKU3 | Product3 | Enabled |
      | PSKU2 | Product2 | Enabled |
      | PSKU1 | Product1 | Enabled |
    And number of records should be 3

  Scenario: Import Product Images
    Given I open "Product Images" import tab
    When I download "Product Images" Data Template file
    And I upload product images files
    And fill template with data:
      | SKU   | Name     | Main | Listing | Additional |
      | PSKU2 | dog1.jpg | 1    | 1       | 1          |
      | PSKU3 | dog1.jpg | 0    | 0       | 1          |
    And I open "Product Images" import tab
    And import file
    Then Email should contains the following "Errors: 0 processed: 2, read: 2, added: 2, updated: 0, replaced: 0" text
    And I click view "PSKU2" in grid
    And I should see "Product Workflow"
    And I should see "dog1.jpg"
    And I should see following product images:
      | dog1.jpg | 1 | 1 | 1 |
    And I go to Products/ Products
    And I click view "PSKU3" in grid
    And I should see "Product Workflow"
    And I should see "dog1.jpg"
    And I should see following product images:
      | dog1.jpg |  |  | 1 |

  Scenario: Check if there Product Images with original file names on frontend on frontend
    Given I proceed as the User
    And I am on the homepage
    When type "PSKU2" in "search"
    And click "Search Button"
    Then should see "Uploaded Product Image" for "PSKU2" product
    When click "View Details" for "PSKU2" product
    Then I should see an "Uploaded Product Image" element
    And "Uploaded Product Image" element "src" attribute should contain "-dog1.jpg"

  Scenario: Disable original product image file names
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Product/Product Images" on configuration sidebar
    And I uncheck "Enable Original File Names"
    And click "Save settings"
    And should see "Configuration saved" flash message

  Scenario: Check if there Product Images without original file names on frontend
    Given I proceed as the User
    When I reload the page
    Then I should see an "Uploaded Product Image" element
    And "Uploaded Product Image" element "src" attribute should not contain "-dog1.jpg"

  Scenario: Import Product Images second time
    Given I proceed as the Admin
    And I go to Products/ Products
    And I open "Product Images" import tab
    When I download "Product Images" Data Template file
    And fill template with data:
      | SKU   | Name     | Main | Listing | Additional |
      | PSKU2 | dog1.jpg | 1    | 1       | 1          |
    And I open "Product Images" import tab
    And import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    And I click view "PSKU2" in grid
    And I should see following product images:
      | dog1.jpg | 1 | 1 | 1 |
      | dog1.jpg |   |   | 1 |
