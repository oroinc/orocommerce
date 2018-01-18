@fixture-OroVisibilityBundle:configurable_product_visibility.yml
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
Feature: Visibility of product variants
  ToDo: BAP-16103 Add missing descriptions to the Behat features

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

    # Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit product_attribute_family_code in grid
    And set Attribute Groups with:
      | Label         | Visible | Attributes |
      | system  group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices] |
      | Size group    | true    | [Size]     |
    And I save form
    Then I should see "Successfully updated" flash message

    # Prepare simple products
    And I go to Products / Products
    And I click Edit SKU2 in grid
    And I fill "ProductForm" with:
      | Size | Large |
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
    And I should see "No records found"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Size] |
    And I check SKU2 record in grid
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
