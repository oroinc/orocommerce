@fixture-OroProductBundle:products_hide_variations.yml
@ticket-BB-11940
@ticket-BB-22211

Feature: Hide simple products that are variations of configurable on front store
  In order to clean up search result and product listings from unnecessary clutter
  As an Admin
  I want to hide simple products created as variations of a configurable product

  Scenario: Create sessions
    Given sessions active:
      | Guest | first_session  |
      | Admin | second_session |

  Scenario: Prepare product attributes
    Given I proceed as the Admin
    Given I login as administrator
    And go to System/ Configuration
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And fill "Shopping List Configuration Form" with:
      | Enable Guest Shopping List Default | false |
      | Enable Guest Shopping List         | true  |
    And click "Save settings"
    When I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And I fill "Display Simple Variations Form" with:
      | Display Simple Variations Default | false           |
      | Display Simple Variations         | Hide completely |
    And I save form
    Then I should see "Configuration saved" flash message

    # Create attribute 1
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Attribute_1 |
      | Type       | Select      |
    And I click "Continue"
    And I fill form with:
      | Label | Attribute_1 |
    And set Options with:
      | Label    |
      | Value 11 |
      | Value 12 |
      | Value 13 |
      | Value 14 |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes    |
      | Attribute group | true    | [Attribute_1] |
    And I save form
    Then I should see "Successfully updated" flash message

    # Prepare content blocks
    And I add New Arrivals widget before content for "Homepage" page
    And I add Featured Products widget after content for "Homepage" page
    And I update settings for "featured-products" content widget:
      | minimum_items | 1 |

  Scenario: Prepare configurable products

    # Variants for CNF_A
    Given I go to Products / Products
    And filter SKU as is equal to "PROD_A_1"
    And I click Edit PROD_A_1 in grid
    And I fill in product attribute "Attribute_1" with "Value 11"
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And filter SKU as is equal to "PROD_A_2"
    And I click Edit PROD_A_2 in grid
    And I fill in product attribute "Attribute_1" with "Value 12"
    And I save form
    Then I should see "Product has been saved" flash message

    # Simple products, not variants of any configurable product
    And I go to Products / Products
    And filter SKU as is equal to "PROD_A_3"
    And I click Edit PROD_A_3 in grid
    And I fill in product attribute "Attribute_1" with "Value 13"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Save configurable products with simple products selected and assign related items
    And I go to Products / Products
    And filter SKU as is equal to "CNF_A"
    And I click Edit CNF_A in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Attribute_1] |
    And I check PROD_A_1 and PROD_A_2 in grid
    And I click "Select related products"
    And I select following records in "SelectRelatedProductsGrid" grid:
      | PROD_A_1  |
      | PROD_B_11 |
      | PROD_C_1  |
      | PROD_C_2  |
    And I click "Select products"
    And I click "Up-sell Products"
    And I click "Select up-sell products"
    And I select following records in "SelectUpsellProductsGrid" grid:
      | PROD_A_2  |
      | PROD_B_12 |
      | PROD_C_1  |
      | PROD_C_2  |
    And I click "Select products"
    And I save form
    Then I should see "Product has been saved" flash message

  # Scenarios related to hide variations feature
  Scenario: Simple product variations are hidden by default from search result
    Given I proceed as the Guest
    When I am on homepage
    And type "Product" in "search"
    And click "Search Button"
    Then I should see "CNF_A" product
    Then I should see "PROD_A_3" product
    Then I should see "PROD_B_11" product
    Then I should see "PROD_B_12" product
    Then I should see "PROD_B_13" product
    Then I should not see "PROD_A_1" product
    Then I should not see "PROD_A_2" product

  Scenario: Simple product variations are hidden by default at homepage
    Given I am on the homepage
    Then I should see the following products in the "New Arrivals Block":
      | SKU       |
      | CNF_A     |
      | PROD_B_12 |
      | PROD_C_1  |
    And should not see the following products in the "New Arrivals Block":
      | SKU      |
      | PROD_A_2 |
    And I should see the following products in the "Featured Products Block":
      | SKU       |
      | CNF_A     |
      | PROD_B_11 |
    And should not see the following products in the "Featured Products Block":
      | SKU      |
      | PROD_A_1 |

  Scenario: Simple product variations are hidden by default from related items blocks
    Given I type "CNF_A" in "search"
    When click "Search Button"
    And I should see "CNF_A" product
    And I click "Configurable Product A"
    Then I should see "Related Products"
     # because it is a variant
    And I should not see "PROD_A_1"
    And I should see "PROD_B_11"
    And I should see "PROD_C_1"
    And I should see "PROD_C_2"
    And I should see "Up-sell Products"
    # because it is a variant
    And I should not see "PROD_A_2"
    And I should see "PROD_B_12"
    And I should see "PROD_C_1"
    And I should see "PROD_C_2"
    When I fill "One Dimensional Matrix Grid Form" with:
      | Value 11 | Value 12 |
      | 1        | 2        |
    When I click "Add to Shopping List"
    Then should see 'Shopping list "Shopping list" was updated successfully' flash message and I close it
    When I open shopping list widget
    Then I should see "Configurable Product A"
    And I should see "PROD_A_1"
    And I should see "PROD_A_2"
    When I click "Configurable Product A"
    Then I should not see "404 Not Found"

  Scenario: Change configuration to display simple variations everywhere
    Given I proceed as the Admin
    And go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And I fill "Display Simple Variations Form" with:
      | Display Simple Variations Default | false      |
      | Display Simple Variations         | everywhere |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Simple product variations made visible in search result
    Given I proceed as the Guest
    When I am on homepage
    And type "Product" in "search"
    And click "Search Button"
    Then I should see "CNF_A" product
    And I should see "PROD_B_11" product
    And I should see "PROD_B_12" product
    And I should see "PROD_B_13" product
    And I should see "PROD_A_1" product
    And I should see "PROD_A_1" product
    And I should see "PROD_A_3" product

  Scenario: Simple prooduct variations made visible at homepage
    Given I am on the homepage
    Then I should see the following products in the "New Arrivals Block":
      | SKU       |
      | CNF_A     |
      | PROD_B_12 |
      | PROD_A_2  |
      | PROD_C_1  |
    And I should see the following products in the "Featured Products Block":
      | SKU       |
      | CNF_A     |
      | PROD_B_11 |
      | PROD_A_1  |

  Scenario: Simple product variations made visible in related item blocks
    Given I type "CNF_A" in "search"
    When click "Search Button"
    And I should see "CNF_A" product
    And I click "Configurable Product A"
    Then I should see "Related Products"
    And I should see "PROD_A_1"
    And I should see "PROD_B_11"
    And I should see "PROD_C_1"
    And I should see "PROD_C_2"
    And I should see "Up-sell Products"
    And I should see "PROD_A_2"
    And I should see "PROD_B_12"
    And I should see "PROD_C_1"
    And I should see "PROD_C_2"
    When I open shopping list widget
    Then I should see "Product A 1"
    And I should see "PROD_A_1"
    And I should see "Product A 2"
    And I should see "PROD_A_2"
    When I click "Product A 2"
    Then I should not see "404 Not Found"
