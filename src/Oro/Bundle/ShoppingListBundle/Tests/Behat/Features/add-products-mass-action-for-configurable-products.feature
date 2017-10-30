@fixture-OroProductBundle:Products_view_page_templates.yml
Feature: Add Products Mass Action for Configurable Products
  In order to add several products in shopping list
  As a Buyer
  I should be able to select and add several products to shopping list

  Scenario: Prepare product attributes
  Scenario: Add new menu item
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

    # Create Size attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label  |
      | L      |
      | M      |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    # Update attribute families
    And I go to Products / Product Families
    And I click Edit Default in grid
    And set Attribute Groups with:
      | Label         | Visible | Attributes |
      | Size group    | true    | [Size]     |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable products
    And I go to Products / Products
    And I click Edit gtsh_l in grid
    And I fill "ProductForm" with:
      | Size   | L |
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And I click Edit rtsh_m in grid
    And I fill "ProductForm" with:
      | Size   | M |
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And I click Edit shirt_main in grid
    And I fill "ProductForm" with:
      | Configurable Attributes | [Size] |
    And I save form
    Then I should see "Product has been saved" flash message
    And I go to Products / Products
    And I click Edit shirt_main in grid
    And I check gtsh_l and rtsh_m in grid
    And I save form
    Then I should see "Product has been saved" flash message
    And I wait for action

  # Add backend check for mass products in action to filter them out
  Scenario: Check that there's no mass action available for configurable product
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org the "Buyer" at "second_session" session
    When I type "shirt_main" in "search"
    And I click "Search Button"
    And I should not see mass action checkbox in row with shirt_main content for "Product Frontend Grid"
    And I should see mass action checkbox in row with gtsh_l content for "Product Frontend Grid"
    And I should see mass action checkbox in row with rtsh_m content for "Product Frontend Grid"

#  Scenario: Grid steps usages for Product Frontend Grid
#    And I check gtsh_l record in "Frontend Grid" grid
#    And I check All Visible records in "Product Frontend Grid"
#    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
#    And I click "Add to current Shopping List" link from mass action dropdown in "Product Frontend Grid"
#    And I select "no-image-view" for product frontend grid
#    And I select "gallery-view" for product frontend grid
#    And I select "list-view" for product frontend grid
