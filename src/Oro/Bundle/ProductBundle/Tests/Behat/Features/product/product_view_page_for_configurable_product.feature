@regression
@ticket-BB-13580
@fixture-OroProductBundle:ProductConfigurableFixture.yml

Feature: Product view page for configurable product
  In order to use product view page for configurable products
  As an Admin
  I need to be able to add configurable products to shopping list from their viw page

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Prepare first product attribute
    Given I proceed as the Admin
    And I login as administrator
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
    Given I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    When I fill "Product Family Form" with:
      | Attributes | [Color,Refurbished] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare first simple product
    Given I go to Products/Products
    And I filter SKU as is equal to "tpc_b_r"
    And I click Edit tpc_b_r in grid
    When I fill in product attribute "Color" with "Black"
    And I fill in product attribute "Refurbished" with "Yes"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare second simple product
    Given I go to Products/Products
    And I filter SKU as is equal to "tpc_w"
    And I click Edit tpc_w in grid
    When I fill in product attribute "Color" with "White"
    And I fill in product attribute "Refurbished" with "No"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Prepare configurable product
    Given I go to Products/Products
    And I filter SKU as is equal to "tpc"
    And I click Edit tpc in grid
    When I fill "ProductForm" with:
      | Configurable Attributes | [Color,Refurbished] |
    And I check tpc_b_r and tpc_w in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Update system configuration
    Given I go to System/Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    When uncheck "Use default" for "Product Views" field
    And I fill in "Product Views" with "No Matrix Form"
    And uncheck "Use default" for "Product Listings" field
    And I fill in "Product Listings" with "No Matrix Form"
    And uncheck "Use default" for "Shopping Lists" field
    And I fill in "Shopping Lists" with "Group Single Products"
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Simple product variations made visible in search result
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I type "tpc" in "search"
    And I click "Search Button"
    And I click "View Details" for "tpc" product
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message
    And I should see "In shopping list"
