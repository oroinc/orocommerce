@fixture-OroVisibilityBundle:configurable_product_visibility.yml
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@ticket-BB-16482
@ticket-BB-20790
Feature: Visibility of product variants

  Scenario: Create different window session
      Given sessions active:
        | Admin     |first_session |
        | Guest     |second_session|

  Scenario: Prepare configurable product
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label |
      | Small |
      | Large |
    And I save form
    Then I should see "Attribute was successfully saved" flash message
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Color   |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label |
      | Red |
      | Green |
    And I save form

    # Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit product_attribute_family_code in grid
    And set Attribute Groups with:
      | Label         | Visible | Attributes |
      | Size group    | true    | [Size, Color] |
    And I save form
    Then I should see "Successfully updated" flash message

    # Prepare simple products
    And I go to Products / Products
    And I click Edit SKU2 in grid
    And I fill "ProductForm" with:
      | Size  | Large |
      | Color | Red   |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And click "More actions"
    And click "Manage Visibility"
    And fill "Visibility Product Form" with:
      |Visibility To All |hidden |
    And I save form
    Then I should see "Product visibility has been saved" flash message

    And I go to Products / Products
    And I click Edit SKU5 in grid
    And I fill "ProductForm" with:
      | Size  | Small |
      | Color | Green   |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And click "More actions"
    And click "Manage Visibility"
    And fill "Visibility Product Form" with:
      |Visibility To All |hidden |
    And I save form
    Then I should see "Product visibility has been saved" flash message

    # Save configurable product with simple products selected
    And I go to Products / Products
    And I click Edit SKU_CONFIGURABLE in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Size] |
    And I check SKU2 record in grid
    And I check SKU5 record in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Unable to add hidden product variant to shopping list
    Given I proceed as the Guest
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "SKU_CONFIGURABLE" in "search"
    And I click "Search Button"
    And I should see "Product Configurable"
    And I click "Product Configurable"
    Then I should not see "Add to Shopping List"

  Scenario: Check visibility add to shopping list button of related products
    Given I proceed as the Admin
    And I go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "RelatedProductsConfig" with:
      | Minimum Items Use Default | false |
      | Minimum Items             | 1     |
    And I save form
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Views" field
    And I fill in "Product Views" with "No Matrix Form"
    And I save form
    Then I go to Products / Products
    And I click View SKU2 in grid
    And click "More actions"
    And click "Manage Visibility"
    And fill "Visibility Product Form" with:
      |Visibility To All | visible |
    And I save form
    Then I should see "Product visibility has been saved" flash message
    And I go to Products / Products
    And I click View SKU5 in grid
    And click "More actions"
    And click "Manage Visibility"
    And fill "Visibility Product Form" with:
      |Visibility To All | visible |
    And I save form
    Then I should see "Product visibility has been saved" flash message
    Then I go to Products / Products
    And I click Edit SKU_CONFIGURABLE in grid
    And I fill "ProductForm" with:
      | Configurable Attributes | [Color] |
    And I click "Select related products"
    And I select following records in "SelectRelatedProductsGrid" grid:
      | SKU3 |
      | SKU4 |
    And I click "Select products"
    And I save and close form
    Given I proceed as the Guest
    And type "SKU_CONFIGURABLE" in "search"
    And I click "Search Button"
    And I click "Product Configurable"
    Then I should see "Add to Shopping List"
    And I select "Please select option" from "Size"
    And I should see "This value should not be blank."
