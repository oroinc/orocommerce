@regression
@fixture-OroProductBundle:default_variant_pagination.yml

Feature: Default variant with pagination
  In order to set default variant for configurable product with many variants
  As an Administrator
  I want to see all checked variants in the Default Variant dropdown across grid pages

  Scenario: Create product attribute
    Given I login as administrator
    And I go to Products/Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label  |
      | Red    |
      | Blue   |
      | Green  |
      | Yellow |
      | Orange |
      | Purple |
      | Pink   |
      | Brown  |
      | Black  |
      | White  |
      | Gray   |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Update attribute family
    Given I go to Products/Product Families
    And I click Edit Default in grid
    And set Attribute Groups with:
      | Label       | Visible | Attributes |
      | Color group | true    | [Color]    |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario Outline: Set color attribute on variant products
    Given I go to Products/Products
    And I click Edit <SKU> in grid
    And I fill "ProductForm" with:
      | Color | <Color> |
    And I save form
    Then I should see "Product has been saved" flash message

    Examples:
      | SKU  | Color  |
      | RSNK | Red    |
      | BJCK | Blue   |
      | GTSH | Green  |
      | YCAP | Yellow |
      | OHDY | Orange |
      | PSRT | Purple |
      | PDRS | Pink   |
      | BBTS | Brown  |
      | BJNS | Black  |
      | WPLO | White  |
      | GCAT | Gray   |

  Scenario: Default variant dropdown should contain variants from all grid pages
    Given I go to Products/Products
    And I click Edit CNFG in grid
    And I fill "ProductForm" with:
      | Configurable Attributes | [Color] |
    And I select 10 from per page list dropdown
    And I sort grid by SKU
    # Check a variant on page 1 and verify it appears in dropdown
    And I check RSNK record in grid
    Then I should see "Default Variant Select" with options:
      | Value                  |
      | - No Default Variant - |
      | Red Sneakers           |
    # Navigate to page 2, check a variant there
    When I fill 2 in page number input
    And I check BBTS record in grid
    Then I should see "Default Variant Select" with options:
      | Value                  |
      | - No Default Variant - |
      | Red Sneakers           |
      | Brown Boots            |
    # Return to page 1 and verify both variants are still in dropdown
    When I fill 1 in page number input
    Then I should see "Default Variant Select" with options:
      | Value                  |
      | - No Default Variant - |
      | Red Sneakers           |
      | Brown Boots            |
    # Select a default variant and save the product
    When I fill "ProductForm" with:
      | Default Variant | Red Sneakers |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Default variant should be preserved after page reload
    Given I go to Products/Products
    And I click Edit CNFG in grid
    Then "ProductForm" must contain values:
      | Default Variant | Red Sneakers |
    And I should see "Default Variant Select" with options:
      | Value                  |
      | - No Default Variant - |
      | Red Sneakers           |
      | Brown Boots            |
