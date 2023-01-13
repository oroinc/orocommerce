@ticket-BB-20130
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroOrderBundle:ConfigurableProductsShoppingList.yml

Feature: Check shopping list dialog in configurable product

  Scenario: Create different window session
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
    When I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size   |
      | Type       | Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | S     |
      | M     |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I go to Products / Product Attributes
    And I click update schema
    Then I should see "Schema updated" flash message

    Then I go to Products / Product Families
    When I click Edit "Default" in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes |
      | Attribute group | true    | [Size] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable product
    Given I go to Products / Products
    When filter SKU as is equal to "PROD_A_1"
    And I click Edit "PROD_A_1" in grid
    And I fill in product attribute "Size" with "S"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "PROD_A_2"
    And I click Edit "PROD_A_2" in grid
    And I fill in product attribute "Size" with "M"
    And I save form
    Then I should see "Product has been saved" flash message
    When I go to Products / Products
    And filter SKU as is equal to "CNFA"
    And I click Edit "CNFA" in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Size] |
    And I check PROD_A_1 and PROD_A_2 in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check title of shopping list dialog in configurable product
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When type "CNFA" in "search"
    And I click "Search Button"
    And I click "View Details" for "CNFA" product
    Then I should see an "Configurable Product Form" element
    When I fill "Configurable Product Form" with:
      | Size | S |
    Then I should see "In shopping list"
    When click "In Shopping List"
    Then I should see "UiDialog" with elements:
      | Title | Product A 1 |
    And I close ui dialog
    When I fill "Configurable Product Form" with:
      | Size | M |
    Then I should see "In shopping list"
    When click "In Shopping List"
    Then I should see "UiDialog" with elements:
      | Title | Product A 2 |
    And I close ui dialog
