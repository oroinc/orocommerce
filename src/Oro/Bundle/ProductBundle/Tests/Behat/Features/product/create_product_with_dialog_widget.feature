@regression
@ticket-BB-7756
@ticket-BB-15782
@automatically-ticket-tagged
@fixture-OroWebCatalogBundle:web_catalog.yml
@fixture-OroProductBundle:ConfigurableProductFixtures.yml
Feature: Create product with dialog widget
  In order to manage products from other pages
  As administrator
  I need to be able to create product with dialog widget and choose products from grid

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Prepare first product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Products/Product Attributes
    And I click "Create Attribute"
    When I fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | Black |
      | White |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Prepare second product attribute
    Given I go to Products/Product Attributes
    And I click "Create Attribute"
    When I fill form with:
      | Field Name | Refurbished |
      | Type       | Boolean     |
    And I click "Continue"
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Schema update
    Given I go to Products/Product Attributes
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family
    Given I proceed as the Admin
    And I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Refurbished] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare first simple product
    Given I proceed as the Admin
    And I go to Products/Products
    And I filter SKU as is equal to "1GB81"
    And I click Edit 1GB81 in grid
    And I fill in product attribute "Refurbished" with "Yes"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare second simple product
    Given I proceed as the Admin
    And I go to Products/Products
    And I filter SKU as is equal to "1GB82"
    And I click Edit 1GB82 in grid
    And I fill in product attribute "Refurbished" with "No"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare configurable product
    Given I proceed as the Admin
    And I go to Products/Products
    And I filter SKU as is equal to "1GB83"
    And I click Edit 1GB83 in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [Refurbished] |
    And I check 1GB81 and 1GB82 in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Create new product from web catalog product variant creation page
    Given I proceed as the Admin
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add Product Page"
    And I click "Content Variants"
    Then "Content Variant" must contains values:
      | Product | Choose Product... |
    When I click on "Create Product Plus Button"
    Then I should see "Create Product"
    And I should see "Continue"
    When I click "Continue" in modal window
    Then I should see "Create Product"
    And I should see "SKU"
    And I should see "Save"
    Then I close ui dialog

  Scenario: Select simple product from product selection grid
    Given I proceed as the Admin
    When I open select entity popup for field "Product"
    Then I should see following grid:
      | SKU   |
      | 1GB83 |
      | 1GB82 |
      | 1GB81 |
    And I click on 1GB81 in grid
    Then I should see "1GB81 - Black Slip-On Clog L"

  Scenario: Select configurable product from product selection grid
    Given I proceed as the Admin
    When I open select entity popup for field "Product"
    Then I should see following grid:
      | SKU   |
      | 1GB83 |
      | 1GB82 |
      | 1GB81 |
    And I click on 1GB83 in grid
    Then I should see "1GB83 - Slip-On Clog"

  Scenario: Select simple product using select autocomplete
    Given I proceed as the Admin
    When I fill "Content Variant" with:
      | Product | 1GB81 |
    Then I should see "1GB81 - Black Slip-On Clog L"
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check simple product is shown in the front office
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "Item #: 1GB81"

  Scenario: Select configurable product using select autocomplete
    Given I proceed as the Admin
    When I fill "Content Variant" with:
      | Product | 1GB83 |
    Then I should see "1GB83 - Slip-On Clog"
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check configurable product is shown in the front office
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "Item #: 1GB83"
