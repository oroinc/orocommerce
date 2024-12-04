@ticket-BB-23529
@fixture-OroProductBundle:Default_product_variant.yml
@regression

Feature: Default product variant
  Scenario: Create different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Prepare product attributes
    Given I proceed as the Admin
    When I login as administrator
    And I go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And uncheck "Use default" for "Product Views" field
    And I fill in "Product Views" with "No Matrix Form"
    Then I save form
    And I should see "Configuration saved" flash message

    # Create Color attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label  |
      | Green  |
      | Red    |
      | Yellow |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Create Size attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size  |
      | Type       | Select |
    And I click "Continue"
    And set Options with:
      | Label  |
      | L      |
      | M      |
      | S      |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Update schema
    When I go to Products / Product Attributes
    And I confirm schema update
    Then I should see "Schema updated" flash message

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit Product Attribute Family in grid
    And set Attribute Groups with:
      | Label         | Visible | Attributes |
      | T-shirt group | true    | [Color, Size] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable products
    And I go to Products / Products
    And I click Edit gtsh_l in grid
    And I fill "ProductForm" with:
      | Color | Green |
      | Size  | L     |
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And I click Edit rtsh_m in grid
    And I fill "ProductForm" with:
      | Color | Red     |
      | Size  | M       |
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Set default product variant while creating a configurbale product
    When I go to Products/ Products
    And click "Create Product"
    And fill "ProductForm Step One" with:
      | Type           | Configurable             |
      | Product Family | Product Attribute Family |
    And I click "Continue"
    And fill "ProductForm" with:
      | SKU              | shirt_102 |
      | Name             | Shirt 2   |
      | Unit of Quantity | item      |
      | Status           | Enabled   |
    Then Default Variant Select has no options
    When I fill "ProductForm" with:
      | Configurable Attributes | [Color, Size] |
    And I check gtsh_l and rtsh_m in grid
    Then I should see "Default Variant Select" with options:
      | Value                    |
      |  - No Default Variant -  |
      | Green shirt L            |
      | Red shirt M              |
    When I fill "ProductForm" with:
      | Default Variant Select | Red shirt M |
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And I click View shirt_102 in grid
    And I press "Product Variants"
    Then I should see following grid:
      | Default variant | SKU    | Name          |
      | Yes             | rtsh_m | Red shirt M   |
      | No              | gtsh_l | Green shirt L |

  Scenario: Check default product on front store product view page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When type "Shirt 2" in "search"
    And I click "Search Button"
    And I click "View Details" for "Shirt 2" product
    Then I should see an "Configurable Product Form" element
    And "Configurable Product Form" must contains values:
      | Color | Red |
      | Size  | M   |

  Scenario: Edit default product variant in configurable product
    Given I proceed as the Admin
    When I go to Products/ Products
    And I click Edit shirt_102 in grid
    When I fill "ProductForm" with:
      | Default Variant Select | Green shirt L |
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And I click View shirt_102 in grid
    And I press "Product Variants"
    Then I should see following grid:
      | Default variant | SKU    | Name          |
      | Yes             | gtsh_l | Green shirt L |
      | No              | rtsh_m | Red shirt M   |

  Scenario: Check updated default product on front store product view page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When type "Shirt 2" in "search"
    And I click "Search Button"
    And I click "View Details" for "Shirt 2" product
    Then I should see an "Configurable Product Form" element
    And "Configurable Product Form" must contains values:
      | Color | Green |
      | Size  | L     |
