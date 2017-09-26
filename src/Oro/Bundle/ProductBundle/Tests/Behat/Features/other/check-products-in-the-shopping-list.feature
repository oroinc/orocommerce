@ticket-BB-10500
@fixture-OroProductBundle:check_products_in_the_shopping_list.yml
Feature: Check configurable product has attributes in the shopping list
  In order to buy products on e-commerce site
  As a Buyer
  I should see product attributes of configurable product in my shopping lists

  Scenario: Check configurable product in the shopping list
    # Prepare product attributes
    Given I login as administrator

    ## Create Color attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | color_attribute  |
      | Type       | Select |
    And I click "Continue"
    And I fill form with:
      | Label      | Color Attribute  |
      | Filterable | Yes  |
    And set Options with:
      | Label  |
      | Green  |
      | Red    |
      | Yellow |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    ## Create Size attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | size_attribute  |
      | Type       | Boolean |
    And I click "Continue"
    And I fill form with:
      | Label | Size Attribute |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    ## Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    ## Update attribute family
    And I go to Products / Product Families
    And I click Edit T_shirt in grid
    And set Attribute Groups with:
      | Label         | Visible | Attributes |
      | T-shirt group | true    | [SKU, Name, Is Featured, New Arrival, Brand, Description, Short Description, Images, Inventory Status, Meta title, Meta description, Meta keywords, Product prices, Color Attribute, Size Attribute] |
    And I save form
    Then I should see "Successfully updated" flash message

    # Prepare simple products
    And I go to Products / Products
    And I click Edit gtsh_l in grid
    And I fill "ProductForm" with:
      | Color Attribute | Green |
      | Size Attribute  | Yes     |
    And I save form
    Then I should see "Product has been saved" flash message

    # Save configurable product with simple products selected
    And I go to Products / Products
    And I click Edit shirt_101 in grid
    And I should see "No records found"
    And I fill "ProductForm" with:
      | Configurable Attributes | [color_attribute, size_attribute] |
    And I check gtsh_l record in grid
    And I save form
    Then I should see "Product has been saved" flash message

    # Check if in the shopping list on front store shown correct attribute labels
    Given I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on Shopping List 1
    Then I should see text matching "Color Attribute: Green"
    And I should see text matching "Size Attribute: Yes"
    Then I should not see text matching "color_attribute"
    And I should not see text matching "size_attribute"

  Scenario: Pre-fill Matrix Order Form
    Given I open product with sku "shirt_101" on the store frontend
    And I should see "Order with Matrix Grid"
    And I click "Order with Matrix Grid"
    And I fill "Shirt_101 Matrix Grid Order Form" with:
      | Green Yes Quantity | 100 |
    And I click "Add to Shopping List" in matrix order window
    And I should see "Product has been added to" flash message
    When I click "Order with Matrix Grid"
    Then "Shirt_101 Matrix Grid Order Form" must contains values:
      | Green Yes Quantity | 100 |
