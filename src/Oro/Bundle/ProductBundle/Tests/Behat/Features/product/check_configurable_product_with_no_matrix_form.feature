@ticket-BB-19541
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:ConfigurableProductFixtures.yml

Feature: Check configurable product with no matrix form
  In order to check configurable product on product listing page with no matrix form
  As a customer
  I go to product listing page and see configurable product without QTY and Unit form fields

  Scenario: Create different window session
    Given sessions active:
    | Admin |first_session  |
    | User  |second_session |

  Scenario: Prepare product attributes
    Given I login as administrator
    And I go to Products/ Product Attributes
    And click "Create Attribute"
    And fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | Black |
      | White |
    And save and close form
    And I click "Create Attribute"
    And fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And click "Continue"
    And set Options with:
      | Label |
      | L     |
      | M     |
    When I save and close form
    And click update schema
    Then I should see Schema updated flash message

  Scenario: Prepare product family
    Given I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color, Size] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare first simple product
    When I go to Products/Products
    And I click Edit 1GB81 in grid
    And I fill in product attribute "Color" with "Black"
    And I fill in product attribute "Size" with "L"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare second simple product
    When I go to Products/Products
    And I click Edit 1GB82 in grid
    And I fill in product attribute "Color" with "White"
    And I fill in product attribute "Size" with "L"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare configurable product
    When I go to Products/Products
    And I click Edit 1GB83 in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    And I check records in grid:
      | 1GB81 |
      | 1GB82 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Update system configuration
    Given I go to System/Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Listings" field
    And I fill in "Product Listings" with "No Matrix Form"
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check configurable product on storefront
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "1GB83" in "search"
    And click "Search Button"
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    And I should see "ProductLineItemForm" element inside "ProductFrontendRow" element
    And I should not see "FrontendProductViewQuantityField" element inside "ProductLineItemForm" element
    Then I should not see "ProductUnitSelect" element inside "ProductLineItemForm" element
